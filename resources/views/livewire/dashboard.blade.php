<div class="space-y-4">
    <div class="card p-6">
        <h2 class="text-lg font-semibold text-gray-900">
            Welcome, {{ auth()->user()->name }}!
        </h2>
        <p class="text-gray-500 mt-1 text-sm">
            {{ auth()->user()->shop?->name }}
        </p>
    </div>
</div>
