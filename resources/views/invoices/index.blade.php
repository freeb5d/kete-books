@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('app.nav.invoices') }}</h1>
        <a href="{{ route('invoices.create') }}" class="bg-emerald-600 text-white rounded px-4 py-1.5 text-sm">{{ __('invoices.new_invoice') }}</a>
    </div>

    @if ($invoices->isEmpty())
        <p class="text-gray-500">{{ __('invoices.no_invoices') }}</p>
    @else
        <div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500 uppercase border-b">
                    <tr>
                        <th class="px-4 py-2">{{ __('invoices.number') }}</th>
                        <th class="px-4 py-2">{{ __('app.labels.customer') }}</th>
                        <th class="px-4 py-2">{{ __('app.labels.issue_date') }}</th>
                        <th class="px-4 py-2">{{ __('app.labels.due_date') }}</th>
                        <th class="px-4 py-2">{{ __('app.labels.status') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('app.labels.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td class="px-4 py-2"><a href="{{ route('invoices.show', $invoice) }}" class="text-emerald-700 font-medium">{{ $invoice->number }}</a></td>
                            <td class="px-4 py-2">{{ $invoice->customer->name }}</td>
                            <td class="px-4 py-2">{{ $invoice->issue_date->toDateString() }}</td>
                            <td class="px-4 py-2">{{ $invoice->due_date->toDateString() }}</td>
                            <td class="px-4 py-2">{{ __('invoices.status.'.$invoice->displayStatus()) }}</td>
                            <td class="px-4 py-2 text-right">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $invoices->links() }}</div>
    @endif
@endsection
