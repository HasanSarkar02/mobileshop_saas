<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

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
        $logs     = collect();
        $hasTable = false;

        try {
            if (Schema::hasTable('activity_log')) {
                $hasTable = true;
                $shopId   = Auth::user()->shop_id;

                $logs = Activity::query()
                    ->with(['causer', 'subject'])
                    ->whereHasMorph('causer', [User::class], function ($q) use ($shopId) {
                        $q->where('shop_id', $shopId);
                    })
                    ->when($this->search, function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('description', 'like', "%{$this->search}%")
                                ->orWhere('event', 'like', "%{$this->search}%")
                                ->orWhereHasMorph('causer', [User::class], function ($u) {
                                    $u->where('name', 'like', "%{$this->search}%");
                                });
                        });
                    })
                    ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
                    ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
                    ->latest()
                    ->paginate(30);
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return view('livewire.settings.activity-log-viewer', compact('logs', 'hasTable'));
    }
}