<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Payment</title>
    <style>
        body { font-family: sans-serif; margin: 2em; max-width: 500px; text-align: center; background-color: #f4f4f9; }
        .container { background: white; padding: 2em; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        p { color: #555; }
        .account-details { list-style: none; padding: 0; margin: 2em 0; border: 1px dashed #ccc; border-radius: 8px; }
        .account-details li { padding: 1em; border-bottom: 1px solid #eee; }
        .account-details li:last-child { border-bottom: none; }
        .account-details strong { display: block; font-size: 0.9em; color: #888; margin-bottom: 0.25em; }
        .footer-note { font-size: 0.9em; color: #777; margin-top: 2em; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Please Complete Your Bank Transfer</h1>
        <p>To complete your payment, please transfer the exact amount to the bank account details below. Your transaction will be confirmed automatically once payment is received.</p>

        {{-- We will pass the bank details from the controller to this view --}}
        <ul class="account-details">
            <li>
                <strong>Amount to Pay</strong>
                {{-- Make sure we have the 'amount' variable --}}
                NGN {{ number_format($details['amount'] ?? 0, 2) }}
            </li>
            <li>
                <strong>Bank Name</strong>
                 {{-- The path to this data might change based on what you find --}}
                {{ $details['bank_name'] ?? 'N/A' }}
            </li>
            <li>
                <strong>Account Number</strong>
                 {{-- The path to this data might change based on what you find --}}
                {{ $details['account_number'] ?? 'N/A' }}
            </li>
        </ul>

        <p class="footer-note">This account is reserved for your transaction only. Please make sure to transfer the exact amount.</p>
    </div>

</body>
</html>