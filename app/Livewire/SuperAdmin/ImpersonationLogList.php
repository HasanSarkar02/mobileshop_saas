<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\UserType;
use App\Models\ImpersonationLog;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Impersonation Logs')]
class ImpersonationLogList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $adminFilter = '';

    #[Url]
    public string $activeOnly = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingAdminFilter(): void { $this->resetPage(); }
    public function updatingActiveOnly(): void { $this->resetPage(); }

    #[Computed]
    public function logs()
    {
        return ImpersonationLog::with(['superAdmin', 'target', 'shop'])
            ->when($this->search, fn ($q) =>
                $q->whereHas('target', fn ($tq) =>
                    $tq->where('name', 'like', "%{$this->search}%")
                       ->orWhere('email', 'like', "%{$this->search}%")
                )
                ->orWhereHas('shop', fn ($sq) =>
                    $sq->where('name', 'like', "%{$this->search}%")
                )
            )
            ->when($this->adminFilter, fn ($q) => $q->where('super_admin_id', $this->adminFilter))
            ->when($this->activeOnly === '1', fn ($q) => $q->whereNull('ended_at'))
            ->latest('started_at')
            ->paginate(25);
    }

    #[Computed]
    public function admins()
    {
        return User::where('user_type', UserType::SuperAdmin)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.admin.impersonation-log-list');
    }
}