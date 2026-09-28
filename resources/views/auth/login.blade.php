<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.auth.log_in') }} — {{ __('app.app_name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex items-center justify-center px-4">
    <form method="POST" action="{{ url('/login') }}" class="bg-white rounded-lg shadow-sm border p-6 w-full max-w-sm space-y-4">
        @csrf
        <h1 class="text-xl font-bold text-emerald-700">🧺 {{ __('app.app_name') }}</h1>

        <div>
            <label for="email" class="block text-xs text-gray-500 mb-1">{{ __('app.auth.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full border rounded px-2 py-1">
            @error('email')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-xs text-gray-500 mb-1">{{ __('app.auth.password') }}</label>
            <input id="password" name="password" type="password" required class="w-full border rounded px-2 py-1">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> {{ __('app.auth.remember') }}
        </label>

        <button class="w-full bg-emerald-600 text-white rounded px-4 py-2">{{ __('app.auth.log_in') }}</button>
    </form>
</body>
</html>
