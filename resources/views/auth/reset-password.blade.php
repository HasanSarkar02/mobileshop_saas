<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password — ShopSaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="h-full bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center px-4 font-sans">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-indigo-700">ShopSaaS</h1>
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Set your password</h2>
            <p class="text-sm text-gray-500 mb-5">Choose a strong password for your account.</p>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <div>
                    <label for="email" class="label">Email</label>
                    <input type="email" id="email" name="email" value="{{ $request->email ?? old('email') }}"
                        required class="input bg-gray-50" readonly>
                </div>
                <div>
                    <label for="password" class="label">New Password</label>
                    <input type="password" id="password" name="password" required autofocus
                        class="input {{ $errors->has('password') ? 'input-error' : '' }}">
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="input">
                </div>
                <button type="submit" class="btn-primary w-full">Set Password & Sign In</button>
            </form>
        </div>
    </div>
</body>

</html>
