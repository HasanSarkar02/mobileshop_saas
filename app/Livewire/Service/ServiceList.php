<?php
namespace App\Livewire\Service;

use App\Enums\ServiceTicketStatus;
use App\Models\ServiceTicket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Service & Repair')]
class ServiceList extends Component
{
    use WithPagination;
    use \App\Traits\HasAuthorization;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->requirePermission('service.view');
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'active'   => ServiceTicket::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'ready'    => ServiceTicket::where('status', ServiceTicketStatus::Ready->value)->count(),
            'due'      => (float) ServiceTicket::whereNotIn('status', ['delivered', 'cancelled'])
                             ->sum('amount_due'),
            'this_month_revenue' => (float) \App\Models\ServicePayment::withoutGlobalScopes()
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
        ];
    }

    public function render()
    {
        $tickets = ServiceTicket::with(['technician', 'branch'])
            ->when($this->search, fn ($q) =>
                $q->where('ticket_number', 'like', "%{$this->search}%")
                  ->orWhere('customer_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_phone', 'like', "%{$this->search}%")
                  ->orWhere('device_model', 'like', "%{$this->search}%")
                  ->orWhere('device_imei', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.service.service-list', compact('tickets'));
    }
}