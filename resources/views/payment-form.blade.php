<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Make a Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-center mb-6">
                        {{-- Make sure your logo is named logo.jpg or update the path here --}}
                        <img src="{{ asset('images/logo.jpg') }}" alt="Company Logo" class="h-16 w-auto">
                    </div>
                    
                    <h3 class="text-lg text-center font-medium text-gray-900 mb-2">
                        Lotto Wallet Top-Up
                    </h3>
                    <p class="mb-6 text-sm text-center text-gray-600">
                        Please select your payment method and enter the amount you wish to add to your account.
                    </p>

                    {{-- Display success or error messages --}}
                    @if(session('success'))
                        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form action="{{ route('payment.initiate') }}" method="POST" id="payment-form">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" name="email" :value="auth()->user()->email" required readonly />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="amount" :value="__('Amount (NGN)')" />
                                <x-text-input id="amount" class="block mt-1 w-full" type="number" name="amount" :value="old('amount')" required autofocus />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-800 mb-3">Select Payment Method</h4>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    {{-- Applying your blue color to the radio button --}}
                                    <input type="radio" name="payment_method" value="card" class="text-[#0074F8] focus:ring-[#0074F8]" checked>
                                    <span class="ml-2 text-sm text-gray-700">Pay with Card</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="bank_transfer" class="text-[#0074F8] focus:ring-[#0074F8]">
                                    <span class="ml-2 text-sm text-gray-700">Pay with Bank Transfer</span>
                                 </label>
                            </div>
                        </div>

                        <div id="card-details" class="space-y-4">
                            <div>
                                <x-input-label for="card_number" :value="__('Card Number')" />
                                <x-text-input id="card_number" name="card_number" class="block mt-1 w-full" type="text" placeholder="0000 0000 0000 0000" />
                                <x-input-error :messages="$errors->get('card_number')" class="mt-2" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="expiry_date" :value="__('Expiry Date')" />
                                    <x-text-input id="expiry_date" name="expiry_date" class="block mt-1 w-full" type="text" placeholder="MM / YY" />
                                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="cvv" :value="__('CVV')" />
                                    <x-text-input id="cvv" name="cvv" class="block mt-1 w-full" type="text" placeholder="123" />
                                    <x-input-error :messages="$errors->get('cvv')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div id="bank-transfer-details" class="hidden">
                             <div>
                                <x-input-label for="bank_code" :value="__('Select Your Bank')" />
                                <select name="bank_code" id="bank_code" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="058">Guaranty Trust Bank</option>
                                    <option value="035">Wema Bank</option>
                                    <option value="044">Access Bank</option>
                                </select>
                                <x-input-error :messages="$errors->get('bank_code')" class="mt-2" />
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-end">
                            <x-primary-button>
                                {{ __('Proceed to Payment') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
            const cardDetails = document.getElementById('card-details');
            const bankDetails = document.getElementById('bank-transfer-details');
            const cardInputs = cardDetails.querySelectorAll('input');
            const bankSelect = bankDetails.querySelector('select');

            function toggleSections() {
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;

                if (selectedMethod === 'card') {
                    cardDetails.classList.remove('hidden');
                    bankDetails.classList.add('hidden');
                    cardInputs.forEach(input => input.required = true);
                    bankSelect.required = false;
                } else { // bank_transfer
                    cardDetails.classList.add('hidden');
                    bankDetails.classList.remove('hidden');
                    cardInputs.forEach(input => input.required = false);
                    bankSelect.required = true;
                }
            }

            paymentMethodRadios.forEach(radio => radio.addEventListener('change', toggleSections));
            
            toggleSections();
        });
    </script>
    @endpush

</x-app-layout>