<?php
namespace App\Livewire\Service;

use App\Actions\RecordServicePaymentAction;
use App\Enums\ServiceTicketStatus;
use App\Models\Account;
use App\Models\BranchStock;
use App\Models\PaymentAccount;
use App\Models\ServiceTicket;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Service Ticket')]
class ServiceTicketDetail extends Component
{
    use \App\Traits\HasAuthorization;

    public ServiceTicket $ticket;

    public string $newStatus       = '';
    public bool   $showPayForm     = false;
    public string $payAmount       = '';
    public int    $payAccountId    = 0;
    public string $payDate         = '';
    public string $payNotes        = '';
    public bool   $showDeductParts = false;

    public function mount(ServiceTicket $ticket): void
    {
        $this->requirePermission('service.view');
        $this->ticket  = $ticket->load(['parts.variant.product', 'payments.paymentAccount', 'customer', 'technician', 'productUnit', 'branch']);
        $this->payDate = now()->format('Y-m-d');
        $this->payAmount = (string) $ticket->balanceDue();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    public function updateStatus(): void
    {

        $this->requirePermission('service.manage');

        if (empty($this->newStatus)) return;

        $targetStatus = ServiceTicketStatus::from($this->newStatus);

        if (! in_array($targetStatus, $this->ticket->status->nextStatuses())) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => "Cannot move to {$targetStatus->label()} from {$this->ticket->status->label()}."]);
            return;
        }

        $updates = ['status' => $this->newStatus];

        if ($targetStatus === ServiceTicketStatus::Ready) {
            $updates['ready_at'] = now();
        }
        if ($targetStatus === ServiceTicketStatus::Delivered) {
            $updates['delivered_at'] = now();
            // Deduct inventory parts on delivery
            if ($this->showDeductParts) {
                $this->deductInventoryParts();
            }
            try {
                $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
                app(\App\Services\SmsService::class)->sendServiceReady($shop, $this->ticket);
            } catch (\Throwable) {}
        }

        $this->ticket->update($updates);
        $this->ticket->refresh()->load('parts.variant.product', 'payments.paymentAccount');
        $this->newStatus = '';
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Status updated to {$targetStatus->label()}."]);
    }

    private function deductInventoryParts(): void
    {
        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $accounting = app(AccountingService::class);

        foreach ($this->ticket->parts->where('from_inventory', true) as $part) {
            if (! $part->product_variant_id) continue;

            $stock = BranchStock::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('branch_id', $this->ticket->branch_id)
                ->where('product_variant_id', $part->product_variant_id)
                ->first();

            if ($stock && $stock->quantity >= $part->quantity) {
                $stock->decrement('quantity', $part->quantity);

                // Journal: Dr Cost of Service Parts / Cr Inventory
                $partsAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '5020')->firstOrFail();
                $invAcc = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)->where('code', '1200')->firstOrFail();

                $cost = (float) $stock->average_cost * $part->quantity;

                if ($cost > 0) {
                    $accounting->postEntry(
                        shop: $shop,
                        description: "Service parts used — {$this->ticket->ticket_number}",
                        lines: [
                            ['account_id' => $partsAcc->id, 'debit'  => $cost],
                            ['account_id' => $invAcc->id,   'credit' => $cost],
                        ],
                        reference: $this->ticket,
                        branchId: $this->ticket->branch_id,
                        actor: Auth::user(),
                    );
                }
            }
        }
    }

    public function recordPayment(RecordServicePaymentAction $action): void
    {
        $this->validate([
            'payAmount'    => 'required|numeric|min:0.01',
            'payAccountId' => 'required|integer|min:1',
            'payDate'      => 'required|date',
        ]);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $action->execute($this->ticket, [
                'amount'             => (float) $this->payAmount,
                'payment_account_id' => $this->payAccountId,
                'payment_date'       => $this->payDate,
                'notes'              => $this->payNotes ?: null,
            ], $shop, Auth::user());

            $this->ticket->refresh()->load('payments.paymentAccount');
            $this->showPayForm = false;
            $this->payAmount   = (string) $this->ticket->balanceDue();
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Payment ৳" . number_format((float) $this->payAmount, 2) . " recorded."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.service.service-ticket-detail');
    }
}