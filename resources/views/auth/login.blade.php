<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — SmartShop ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center px-4 font-sans">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            @php $settings = App\Models\PlatformSetting::current(); @endphp

            @if ($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}"
                    alt="{{ $settings->app_name ?? 'SmartShop ERP' }}" class="mx-auto h-24 w-auto mb-4">
            @else
                <h1 class="text-2xl font-bold text-indigo-700">{{ $settings->app_name ?? 'SmartShop ERP' }}</h1>
            @endif
            <p class="text-gray-500 text-sm mt-1">Sign in to your shop account</p>
        </div>
        <div class="card p-6">
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="input {{ $errors->has('email') ? 'input-error' : '' }}">
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" required
                        class="input {{ $errors->has('password') ? 'input-error' : '' }}">
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Forgot password?
                    </a>
                </div>
                <button type="submit" class="btn-primary w-full">Sign In</button>
            </form>
        </div>
    </div>
    <div class="absolute bottom-6 left-0 right-0 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} SmartShop ERP. Developed by <span class="font-medium text-gray-700">Hasan
            Sarkar</span>.
    </div>
</body>

</html>
