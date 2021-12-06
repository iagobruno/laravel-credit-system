@section('page_title', __('Recharge credits'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recharge') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('recharge.post') }}" method="POST">
                @csrf
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <label for="amount-input" class="text-lg font-semibold">{{ __('Add credits') }}</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="amount"
                            id="amount-input"
                            value="{{ old('amount') ?? '0,00' }}"
                            class="appearance-none border-none bg-transparent p-0 text-5xl w-full focus:outline-none focus:ring-0"
                            required
                            pattern="^[\d,.]+$"
                            autofocus
                        >

                        <div class="flex gap-2 mt-2 mb-4">
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="addAmountToInput(1)">+1</button>
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="addAmountToInput(10)">+10</button>
                            <button type="button" class="border border-gray-300 rounded-lg px-2 py-1 text-xs cursor-pointer hover:border-gray-600 sum" onclick="addAmountToInput(100)">+100</button>
                        </div>

                        @if(!session()->has('show-payment-form'))
                        <x-button type="submit" class="w-full justify-center">{{ __('Recharge') }}</x-button>
                        @endif
                    </div>

                    {{-- @if (session()->has('show-payment-form')) --}}
                    <div class="p-6 border-b border-gray-200">
                        <div class="bg-yellow-100 border border-yellow-300 py-2 px-3 rounded-md mb-2">{{ __('Enter your payment details below') }}</div>
                        <div class="text-lg font-semibold">{{ __('Payment infos') }}</div>

                        <div class="mb-4 mt-2">Mostrar formulário do Stripe</div>

                        <x-button type="submit" class="w-full justify-center">{{ __('Recharge') }}</x-button>
                    </div>
                    {{-- @endif --}}

                </div>
            </form>
        </div>
    </div>

    <script src="https://github.com/codermarcos/simple-mask-money/releases/download/v3.0.0/simple-mask-money.js"></script>
    <script src="/js/recharge-page.js"></script>
</x-app-layout>
