@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ __('app.nav.dashboard') }} — {{ $business->name }}</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <p class="text-xs text-gray-500 uppercase">{{ __('app.labels.total') }} {{ __('app.account_types.income') }}</p>
            <p class="text-2xl font-semibold text-emerald-700">{{ $business->currency }} {{ number_format($totalIncome, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <p class="text-xs text-gray-500 uppercase">{{ __('app.account_types.expense') }}</p>
            <p class="text-2xl font-semibold text-rose-600">{{ $business->currency }} {{ number_format($totalExpense, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <p class="text-xs text-gray-500 uppercase">Net Profit</p>
            <p class="text-2xl font-semibold {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                {{ $business->currency }} {{ number_format($netProfit, 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <p class="text-xs text-gray-500 uppercase">GST Payable</p>
            <p class="text-2xl font-semibold text-amber-600">{{ $business->currency }} {{ number_format($gstPayable, 2) }}</p>
        </div>
    </div>

    @if ($overdueInvoices->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <h2 class="font-semibold mb-3">Overdue Invoices</h2>
            <ul class="divide-y">
                @foreach ($overdueInvoices as $invoice)
                    <li class="py-2 flex justify-between text-sm">
                        <span>{{ $invoice->number }} — {{ $invoice->customer->name }}</span>
                        <span class="text-rose-600 font-medium">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
