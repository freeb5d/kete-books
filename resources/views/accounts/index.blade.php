@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ __('accounts.chart_of_accounts') }}</h1>

    @foreach ($accountsByType as $type => $accounts)
        <div class="mb-6">
            <h2 class="font-semibold text-gray-700 mb-2">{{ __('app.account_types.'.$type) }}</h2>
            <div class="bg-white rounded-lg shadow-sm border divide-y">
                @foreach ($accounts as $account)
                    <div class="flex justify-between px-4 py-2 text-sm">
                        <span>
                            <span class="text-gray-400 font-mono">{{ $account->code }}</span>
                            {{ $account->name }}
                        </span>
                        <span class="font-medium">{{ number_format($account->balance(), 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <form method="POST" action="{{ route('accounts.store') }}" class="bg-white rounded-lg shadow-sm border p-4 mt-8 grid grid-cols-4 gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('accounts.code') }}</label>
            <input name="code" class="w-full border rounded px-2 py-1" required>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('app.labels.description') }}</label>
            <input name="name" class="w-full border rounded px-2 py-1" required>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('app.labels.status') }}</label>
            <select name="type" class="w-full border rounded px-2 py-1">
                @foreach (['asset','liability','equity','income','expense'] as $type)
                    <option value="{{ $type }}">{{ __('app.account_types.'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <button class="bg-emerald-600 text-white rounded px-4 py-1.5">{{ __('accounts.add_account') }}</button>
    </form>
@endsection
