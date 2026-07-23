<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — SmartShop ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    @vite(['resources/css/app.css'])
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
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Reset your password</h2>
            <p class="text-sm text-gray-500 mb-5">Enter your email and we'll send you a link to reset your password.</p>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="input {{ $errors->has('email') ? 'input-error' : '' }}">
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full">Send Reset Link</button>
                <a href="{{ route('login') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700">Back
                    to login</a>
            </form>
        </div>
    </div>
</body>

</html>
