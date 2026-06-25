<?php
namespace App\Actions;

use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\ServicePayment;
use App\Models\ServiceTicket;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecordServicePaymentAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(ServiceTicket $ticket, array $data, Shop $shop, User $actor): ServicePayment
    {
        return DB::transaction(function () use ($ticket, $data, $shop, $actor) {

            if ($ticket->status->isTerminal()) {
                throw new RuntimeException("Cannot record payment on a {$ticket->status->label()} ticket.");
            }

            $amount = (float) $data['amount'];

            $payment = ServicePayment::create([
                'ticket_id'          => $ticket->id,
                'shop_id'            => $shop->id,
                'payment_account_id' => $data['payment_account_id'],
                'amount'             => $amount,
                'payment_date'       => $data['payment_date'],
                'notes'              => $data['notes'] ?? null,
                'created_by'         => $actor->id,
            ]);

            // Update ticket totals
            $ticket->recalculateTotals();

            // Journal: Dr Cash/Bank → Cr Service Revenue
            $serviceRevAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '4030')->firstOrFail();
            $pa    = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $glPa  = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $this->accounting->postEntry(
                shop: $shop,
                description: "Service payment — {$ticket->ticket_number}: {$ticket->customer_name}",
                lines: [
                    ['account_id' => $glPa->id,        'debit'  => $amount, 'description' => "Service payment"],
                    ['account_id' => $serviceRevAcc->id,'credit' => $amount, 'description' => "Service revenue"],
                ],
                reference: $payment,
                branchId: $ticket->branch_id,
                actor: $actor,
            );

            return $payment;
        });
    }
}