<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <title>Recovery Codes — SmartShop ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl space-y-5">
            <h2 class="text-lg font-semibold text-white">Save Your Recovery Codes</h2>
            <p class="text-sm text-amber-400">
                Store these safely. Each works once if you lose your authenticator device. Not shown again.
            </p>
            <div class="bg-slate-800 rounded-lg p-4 font-mono text-sm text-indigo-300 grid grid-cols-2 gap-2">
                @foreach ($codes as $code)
                    <div>{{ $code }}</div>
                @endforeach
            </div>
            <a href="{{ route('admin.dashboard') }}"
                class="block text-center w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-lg">
                I've saved these — Continue to Dashboard
            </a>
        </div>
    </div>
</body>

</html>
