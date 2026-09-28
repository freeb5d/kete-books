@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ $invoice->number }}</h1>
        <span class="text-sm px-2 py-1 rounded bg-gray-100">{{ __('invoices.status.'.$invoice->displayStatus()) }}</span>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div><span class="block text-xs text-gray-500">{{ __('app.labels.customer') }}</span>{{ $invoice->customer->name }}</div>
        <div><span class="block text-xs text-gray-500">{{ __('app.labels.issue_date') }}</span>{{ $invoice->issue_date->toDateString() }}</div>
        <div><span class="block text-xs text-gray-500">{{ __('app.labels.due_date') }}</span>{{ $invoice->due_date->toDateString() }}</div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-gray-500 uppercase border-b">
                <tr>
                    <th class="px-4 py-2">{{ __('app.labels.description') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('invoices.quantity') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('invoices.unit_price') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('app.labels.total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($invoice->lines as $line)
                    <tr>
                        <td class="px-4 py-2">{{ $line->description }} @unless ($line->gst_applicable)<span class="text-xs text-gray-400">({{ __('invoices.no_gst') }})</span>@endunless</td>
                        <td class="px-4 py-2 text-right">{{ $line->quantity }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($line->unit_price, 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="text-right">
                <tr><td colspan="3" class="px-4 py-1 text-gray-500">{{ __('app.labels.subtotal') }}</td><td class="px-4 py-1">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                <tr><td colspan="3" class="px-4 py-1 text-gray-500">{{ __('app.labels.gst') }}</td><td class="px-4 py-1">{{ number_format($invoice->gst_total, 2) }}</td></tr>
                <tr class="font-semibold"><td colspan="3" class="px-4 py-2">{{ __('app.labels.total') }}</td><td class="px-4 py-2">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    @if ($invoice->status === 'draft')
        <form method="POST" action="{{ route('invoices.send', $invoice) }}">
            @csrf
            <button class="bg-emerald-600 text-white rounded px-4 py-1.5">{{ __('invoices.mark_as_sent') }}</button>
        </form>
    @endif
@endsection
