@section('page_title', __('Recharge credits'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recharge') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('recharge.post') }}" method="POST" id="recharge-form">
                @csrf
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <label for="amount-input" class="text-lg font-semibold">{{ __('Add credits') }}</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            id="amount-input"
                            value="{{ old('amount') ?? '0,00' }}"
                            class="appearance-none border-none bg-transparent p-0 text-5xl w-full focus:outline-none focus:ring-0"
                            required
                            pattern="^(\d+.?)+(,\d{2})$"
                            autofocus
                        >
                        <input type="hidden" name="amount">
                        @error('amount')
                        <div class="text-red-700 font-semibold text-sm">Invalid amount</div>
                        @enderror

                        <div class="flex gap-2 mt-2 mb-4">
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="sumToAmountInput(1)">+1</button>
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="sumToAmountInput(10)">+10</button>
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="sumToAmountInput(100)">+100</button>
                        </div>

                        @if(!session()->has('show_payment_form'))
                        <x-button type="submit" class="w-full justify-center">{{ __('Recharge') }}</x-button>
                        @endif
                    </div>

                    @if (session()->has('show_payment_form'))
                    <div class="card-inputs p-6 border-b border-gray-200">
                        <div class="bg-yellow-100 border border-yellow-300 py-2 px-3 rounded-md mb-2">{{ __('Enter your payment details below') }}</div>
                        <div class="text-lg font-semibold mb-2">{{ __('Payment infos') }}</div>

                        <x-label for="card-holder-name-input">{{ __('Holder name') }}</x-label>
                        <x-input text="text" name="card-holder-name" id="card-holder-name-input" value="Fake User" class="border border-gray-300 w-full px-3 py-2 mb-3 mt-1" />

                        <x-label>{{ __('Credit card numbers') }}</x-label>
                        <div id="card-element" class="rounded-md shadow-sm border border-gray-300 p-3 my-2"></div>
                        <div id="card-errors" class="text-red-700 font-semibold mt-1 mb-3"></div>

                        <x-button type="submit" class="w-full justify-center mt-2">{{ __('Recharge') }}</x-button>

                        <script type="application/json" id="stripe-keys">{
                            "setup_secret": "{{ session('setup_intent_secret') }}",
                            "stripe_pub_key": "{{ env('STRIPE_KEY') }}"
                        }</script>
                    </div>
                    @endif

                </div>
            </form>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://github.com/codermarcos/simple-mask-money/releases/download/v3.0.0/simple-mask-money.js"></script>
    <script src="/js/recharge-page.js"></script>
</x-app-layout>
