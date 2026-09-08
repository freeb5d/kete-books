<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.app_name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <nav class="bg-white border-b shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg text-emerald-700">
                🧺 {{ __('app.app_name') }}
            </a>

            <div class="flex items-center gap-6 text-sm">
                <a href="{{ route('dashboard') }}">{{ __('app.nav.dashboard') }}</a>
                <a href="{{ route('accounts.index') }}">{{ __('app.nav.accounts') }}</a>
                <a href="{{ route('invoices.index') }}">{{ __('app.nav.invoices') }}</a>

                {{-- Language switcher: preserves the current route in both locales --}}
                <div class="flex gap-2 border-l pl-4">
                    @foreach (\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                        <a
                            href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($localeCode) }}"
                            class="{{ app()->getLocale() === $localeCode ? 'font-semibold text-emerald-700' : 'text-gray-500' }}"
                        >
                            {{ strtoupper($localeCode) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>
</body>
</html>
