<?php
namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'shop_id', 'payment_account_id', 'amount', 'payment_date', 'notes', 'created_by'])]
class ServicePayment extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(ServiceTicket::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
}