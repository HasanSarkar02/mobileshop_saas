<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Activity Log')]
class ActivityLogViewer extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo   = '';

    public function render()
    {
        $shopId = Auth::user()->shop_id;

        // Use Spatie's activity_log table directly
        $logs = DB::table('activity_log')
            ->join('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('activity_log.causer_type', \App\Models\User::class)
            ->where('users.shop_id', $shopId)
            ->when($this->search, fn ($q) =>
                $q->where('activity_log.description', 'like', "%{$this->search}%")
                  ->orWhere('activity_log.subject_type', 'like', "%{$this->search}%")
                  ->orWhere('users.name', 'like', "%{$this->search}%")
            )
            ->when($this->dateFrom, fn ($q) => $q->whereDate('activity_log.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('activity_log.created_at', '<=', $this->dateTo))
            ->selectRaw('
                activity_log.id, activity_log.description, activity_log.event,
                activity_log.subject_type, activity_log.subject_id,
                activity_log.properties, activity_log.created_at,
                users.name AS user_name
            ')
            ->orderByDesc('activity_log.created_at')
            ->paginate(30);

        return view('livewire.settings.activity-log-viewer', compact('logs'));
    }
}