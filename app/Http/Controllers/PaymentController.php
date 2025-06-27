<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use App\Models\Transaction;

class PaymentController extends Controller
{
    private $apiBaseUrl = 'https://checkout-api.arcapg.com';

    public function showPaymentForm()
    {
        return view('payment-form');
    }

    public function initiatePayment(Request $request)
    {
        // --- NEW: Smart, Conditional Validation ---
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:card,bank_transfer',
            // Card fields are only required if the payment method is 'card'
            'card_number' => 'required_if:payment_method,card',
            'expiry_date' => 'required_if:payment_method,card',
            'cvv' => 'required_if:payment_method,card',
            // Bank code is only required if the payment method is 'bank_transfer'
            'bank_code' => 'required_if:payment_method,bank_transfer',
        ]);

        try {
            // Step 1: Create the transaction in our local database
            $localOrderReference = 'LOTTO-' . strtoupper(Str::random(12));
            Transaction::create([
                'user_id' => auth()->id(),
                'order_reference' => $localOrderReference,
                'amount' => $request->amount,
                'status' => 'pending',
            ]);

            // Step 2: Create the Order with Arca (same for both methods)
            $redirectUrl = route('payment.callback');
            $customerData = ['first_name' => 'Lotto', 'last_name' => 'Player', 'mobile' => '+2348101234544', 'country' => 'NG', 'email' => $request->email];
            $createOrderData = [
                'customer' => $customerData,
                'order' => ['amount' => (int) $request->amount, 'reference' => $localOrderReference, 'description' => 'Lotto Wallet Top-Up', 'currency' => 'NGN'],
                'payment' => ['redirect_url' => $redirectUrl],
            ];

            $encryptedOrderPayload = $this->encryptPayload(json_encode($createOrderData));
            $createOrderResponse = Http::withoutVerifying()->withHeaders(['api-key' => env('ARC_PUBKEY_TEST')])->post($this->apiBaseUrl . '/checkout/order/create', ['data' => $encryptedOrderPayload]);

            if (!$createOrderResponse->successful() || $createOrderResponse->json('status') !== 'success') {
                throw new \Exception('Failed to create order. Response: ' . $createOrderResponse->body());
            }
            
            $arcaReference = $createOrderResponse->json('data.order.reference', $localOrderReference);
            $paymentMethod = $request->input('payment_method');
            $chargeData = [];

            if ($paymentMethod === 'card') {
                list($expiryMonth, $expiryYear) = explode('/', str_replace(' ', '', $request->expiry_date));
                $chargeData = [
                    'reference' => $arcaReference, 'payment_option' => 'C', 'country' => 'NG',
                    'card' => ['cvv' => $request->cvv, 'card_number' => str_replace(' ', '', $request->card_number), 'expiry_month' => $expiryMonth, 'expiry_year' => $expiryYear],
                ];
            } elseif ($paymentMethod === 'bank_transfer') {
                $chargeData = [
                    'reference' => $arcaReference,
                    'payment_option' => 'BANK-TRANSFER',
                    'bank_transfer' => ['bank_code' => $request->bank_code],
                ];
            }
            
            $encryptedPayload = $this->encryptPayload(json_encode($chargeData));
            $payOrderResponse = Http::withoutVerifying()->withHeaders(['api-key' => env('ARC_PUBKEY_TEST')])->post($this->apiBaseUrl . '/checkout/order/pay', ['data' => $encryptedPayload]);
            
            if (!$payOrderResponse->successful() || $payOrderResponse->json('status') !== 'success') {
                throw new \Exception('Failed to process payment. Response: ' . $payOrderResponse->body());
            }

            // Step 4: Handle the successful response
            if ($paymentMethod === 'card') {
                $bankRedirectUrl = $payOrderResponse->json('data.payment_detail.redirect_url');
                return redirect()->away($bankRedirectUrl);

            } elseif ($paymentMethod === 'bank_transfer') {
                $details = $payOrderResponse->json('data.payment_detail');
                return view('show-bank-details', ['details' => $details]);
            }

        } catch (\Exception $e) {
            Log::error('ARCA_PAYMENT_ERROR: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while processing your payment. Please try again.')->withInput();
        }
    }

    public function handleCallback(Request $request)
    {
        $orderReference = $request->query('order_payment_reference');
        $status = $request->query('status');

        if ($status === 'successful') {
            $isVerified = $this->verifyPayment($orderReference);
            if ($isVerified) {
                $transaction = Transaction::where('order_reference', $orderReference)->first();
                if ($transaction && $transaction->status === 'pending') {
                    $transaction->status = 'successful';
                    $transaction->save();
                    $user = $transaction->user;
                    if ($user) {
                        $user->wallet_balance += $transaction->amount;
                        $user->save();
                    }
                }
                return redirect()->route('payment.form')->with('success', 'Payment successful and wallet credited!');
            }
        }
        
        return redirect()->route('payment.form')->with('error', 'Payment was not successful. Please try again.');
    }

    public function verifyPayment($reference)
    {
        try {
            $response = Http::withoutVerifying()->withHeaders(['api-key' => env('ARC_SECKEY_TEST')])->post($this->apiBaseUrl . '/checkout/order/verify', ['reference' => $reference]);
            if ($response->successful() && $response->json('data.status') === 'Successful') {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('ARCA_VERIFY_ERROR: ' . $e->getMessage());
            return false;
        }
    }

    private function encryptPayload($payload)
    {
        $base64EncodedKey = env('ARCA_ENCRYPTION_KEY');
        $publicKeyXML = base64_decode($base64EncodedKey);
        $publicKey = RSA::loadFormat('XML', $publicKeyXML)->withPadding(RSA::ENCRYPTION_PKCS1);
        $encrypted = $publicKey->encrypt($payload);
        return base64_encode($encrypted);
    }
}