<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login — SmartShop ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans bg-slate-950 flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            @php $settings = App\Models\PlatformSetting::current(); @endphp

            @if ($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}"
                    alt="{{ $settings->app_name ?? 'SmartShop ERP' }}" class="mx-auto h-24 w-auto mb-4 ">
            @else
                <h1 class="text-2xl font-bold text-indigo-700">{{ $settings->app_name ?? 'SmartShop ERP' }}</h1>
            @endif
            <p class="text-slate-400 text-sm mt-1">Super Admin Panel</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-6">Sign in to Admin</h2>

            {{-- Session Status --}}
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-900/40 border border-green-800 rounded-lg text-sm text-green-400">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-900/40 border border-red-800 rounded-lg text-sm text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="email"
                        class="block w-full rounded-lg bg-slate-800 border border-slate-700 text-white placeholder-slate-500 text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors
                            {{ $errors->has('email') ? 'border-red-500' : '' }}"
                        placeholder="admin@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Password
                    </label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="block w-full rounded-lg bg-slate-800 border border-slate-700 text-white placeholder-slate-500 text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember"
                        class="h-4 w-4 rounded bg-slate-800 border-slate-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900">
                    <label for="remember" class="ml-2 text-sm text-slate-400 cursor-pointer">
                        Keep me signed in
                    </label>
                </div>

                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Sign In
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-600 mt-6">
            This area is restricted to authorized platform administrators only.
        </p>
    </div>
    <div class="absolute bottom-6 left-0 right-0 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} SmartShop ERP. Developed by <span class="font-medium text-gray-700">Hasan
            Sarkar</span>.
    </div>
</body>

</html>
