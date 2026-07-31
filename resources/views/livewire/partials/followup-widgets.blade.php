<div class="grid sm:grid-cols-3 gap-4">
    <a href="{{ route('customers.due-followups', ['view' => 'today']) }}" wire:navigate
        class="card p-4 border-0 bg-blue-50 hover:bg-blue-100 transition-colors block">
        <div class="text-2xl font-bold text-blue-700">{{ $this->followUpsDueToday->count() }}</div>
        <div class="text-xs font-medium text-blue-500 mt-0.5">Today's Follow-ups</div>
    </a>
    <a href="{{ route('customers.due-followups', ['view' => 'overdue']) }}" wire:navigate
        class="card p-4 border-0 bg-red-50 hover:bg-red-100 transition-colors block">
        <div class="text-2xl font-bold text-red-700">{{ $this->overdueFollowUps->count() }}</div>
        <div class="text-xs font-medium text-red-500 mt-0.5">Overdue Follow-ups</div>
    </a>
    <a href="{{ route('customers.due-followups', ['view' => 'promised']) }}" wire:navigate
        class="card p-4 border-0 bg-amber-50 hover:bg-amber-100 transition-colors block">
        <div class="text-2xl font-bold text-amber-700">{{ $this->brokenPromises->count() }}</div>
        <div class="text-xs font-medium text-amber-500 mt-0.5">Broken Promises</div>
    </a>
</div>
