@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ __('invoices.new_invoice') }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('invoices.store') }}" class="bg-white rounded-lg shadow-sm border p-4 space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="customer_id" class="block text-xs text-gray-500 mb-1">{{ __('app.labels.customer') }}</label>
                <select id="customer_id" name="customer_id" required class="w-full border rounded px-2 py-1">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="issue_date" class="block text-xs text-gray-500 mb-1">{{ __('app.labels.issue_date') }}</label>
                <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required class="w-full border rounded px-2 py-1">
            </div>
            <div>
                <label for="due_date" class="block text-xs text-gray-500 mb-1">{{ __('app.labels.due_date') }}</label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date', now()->addDays(14)->toDateString()) }}" required class="w-full border rounded px-2 py-1">
            </div>
        </div>

        <div>
            <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 mb-1">
                <span class="col-span-6">{{ __('app.labels.description') }}</span>
                <span class="col-span-2">{{ __('invoices.quantity') }}</span>
                <span class="col-span-2">{{ __('invoices.unit_price') }}</span>
                <span class="col-span-2">{{ __('app.labels.gst') }}</span>
            </div>

            <div id="lines" class="space-y-2">
                @foreach (old('lines', [['description' => '', 'quantity' => 1, 'unit_price' => '', 'gst_applicable' => '1']]) as $i => $line)
                    <div class="grid grid-cols-12 gap-2" data-line>
                        <input name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" required class="col-span-6 border rounded px-2 py-1">
                        <input name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" type="number" step="0.01" min="0.01" required class="col-span-2 border rounded px-2 py-1">
                        <input name="lines[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" type="number" step="0.01" min="0" required class="col-span-2 border rounded px-2 py-1">
                        <label class="col-span-2 flex items-center">
                            <input type="hidden" name="lines[{{ $i }}][gst_applicable]" value="0">
                            <input type="checkbox" name="lines[{{ $i }}][gst_applicable]" value="1" @checked(($line['gst_applicable'] ?? '1') == '1')>
                        </label>
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-line" class="mt-2 text-sm text-emerald-700">+ {{ __('invoices.add_line') }}</button>
        </div>

        <button class="bg-emerald-600 text-white rounded px-4 py-1.5">{{ __('app.labels.create') }}</button>
    </form>

    <script>
        document.getElementById('add-line').addEventListener('click', () => {
            const rows = document.querySelectorAll('#lines [data-line]');
            const clone = rows[rows.length - 1].cloneNode(true);
            const index = rows.length;
            clone.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/lines\[\d+\]/, `lines[${index}]`);
                if (input.type === 'checkbox') input.checked = true;
                else if (input.type !== 'hidden') input.value = input.name.endsWith('[quantity]') ? 1 : '';
            });
            document.getElementById('lines').appendChild(clone);
        });
    </script>
@endsection
