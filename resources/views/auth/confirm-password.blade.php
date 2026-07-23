<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Password — ShopSaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="h-full bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center px-4 font-sans">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-indigo-700">ShopSaaS</h1>
        </div>

        <div class="card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Confirm Password</h2>
            <p class="text-sm text-gray-500 mb-5">
                This is a secure area of the application. Please confirm your password before continuing.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" required autofocus
                        class="input {{ $errors->has('password') ? 'input-error' : '' }}">
                    @error('password')
                        <p class="error text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
