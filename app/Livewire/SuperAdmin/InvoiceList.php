<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Subscription Invoices')]
class InvoiceList extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search       = '';

    #[Computed]
    public function invoices()
    {
        return SubscriptionInvoice::withoutGlobalScopes()
            ->with(['shop', 'subscription.plan'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) =>
                $q->whereHas('shop', fn ($sq) =>
                    $sq->where('name', 'like', "%{$this->search}%")
                )
            )
            ->orderByDesc('due_date')
            ->paginate(30);
    }

    #[Computed]
    public function totals(): object
    {
        return (object) [
            'pending' => (float) SubscriptionInvoice::withoutGlobalScopes()->where('status', 'pending')->sum('amount'),
            'paid'    => (float) SubscriptionInvoice::withoutGlobalScopes()->where('status', 'paid')->sum('amount'),
            'overdue' => SubscriptionInvoice::withoutGlobalScopes()
                ->where('status', 'pending')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    public function markInvoicePaid(int $id): void
    {
        $invoice = SubscriptionInvoice::withoutGlobalScopes()->findOrFail($id);
        $invoice->update([
            'status'  => 'paid',
            'paid_at' => now()->toDateString(),
            'payment_method' => 'manual',
        ]);

        unset($this->invoices, $this->totals);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Invoice marked as paid.']);
    }

    public function waive(int $id): void
    {
        SubscriptionInvoice::withoutGlobalScopes()->findOrFail($id)->update(['status' => 'waived']);
        unset($this->invoices, $this->totals);
        $this->dispatch('notify', ['type' => 'warning', 'message' => 'Invoice waived.']);
    }

    public function render()
    {
        return view('livewire.admin.invoice-list');
    }
}