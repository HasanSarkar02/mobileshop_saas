<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification — SmartShop ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl space-y-5">

            @if ($errors->any())
                <div class="p-3 bg-red-900/40 border border-red-800 rounded-lg text-sm text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($needsSetup)
                <h2 class="text-lg font-semibold text-white">Set Up Two-Factor Authentication</h2>
                <p class="text-sm text-slate-400">Scan with Google Authenticator, Authy, or any TOTP app. Required
                    before accessing the admin panel.</p>
                <div class="bg-white p-3 rounded-xl flex justify-center">
                    <img src="{{ $setupData['qrUrl'] }}" alt="2FA QR Code" width="220" height="220">
                </div>
                <div class="text-xs text-slate-400">
                    Can't scan? Enter this key manually:
                    <div class="font-mono text-indigo-300 mt-1 break-all">{{ $setupData['secret'] }}</div>
                </div>
                <p class="text-sm text-slate-400">Enter the 6-digit code from your app to confirm setup:</p>
            @else
                <h2 class="text-lg font-semibold text-white">Two-Factor Verification</h2>
                <p class="text-sm text-slate-400">Enter the 6-digit code from your authenticator app, or a recovery
                    code.</p>
            @endif

            <form method="POST" action="{{ route('admin.2fa.verify') }}" class="space-y-4">
                @csrf
                <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric"
                    placeholder="6-digit code or recovery code"
                    class="block w-full rounded-lg bg-slate-800 border border-slate-700 text-white placeholder-slate-500 text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors">
                    Verify & Continue
                </button>
            </form>

            <a href="{{ route('admin.login') }}" class="block text-center text-xs text-slate-500 hover:text-slate-300">
                ← Back to login
            </a>
        </div>
    </div>
</body>

</html>
