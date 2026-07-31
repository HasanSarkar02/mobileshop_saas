<?php

namespace App\Livewire\Customers;

use App\Enums\FollowUpStatus;
use App\Enums\PermissionEnum;
use App\Models\CustomerDueFollowUp;
use App\Traits\HasAuthorization;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Due Follow-ups')]
class CustomerFollowUpList extends Component
{
    use HasAuthorization, WithPagination;

    #[Url(as: 'view')]
    public string $view = 'today'; // today | overdue | promised | all

    public function updatingView(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->requirePermission(PermissionEnum::CustomersManageFollowups->value);
    }

    private function baseQuery()
    {
        $query = CustomerDueFollowUp::with('customer')
            ->whereNull('completed_at')
            ->whereNotIn('status', [FollowUpStatus::Paid->value, FollowUpStatus::Cancelled->value]);

        return match ($this->view) {
            'overdue'  => $query->whereDate('next_followup_date', '<', today()),
            'promised' => $query->whereNotNull('promised_payment_date')
                ->whereDate('promised_payment_date', '<', today())
                ->whereHas('customer', fn ($q) => $q->where('current_balance', '>', 0)),
            'today'    => $query->whereDate('next_followup_date', today()),
            default    => $query,
        };
    }

    public function render()
    {
        return view('livewire.customers.customer-followup-list', [
            'followUps' => $this->baseQuery()->orderBy('next_followup_date')->paginate(20),
        ]);
    }
}