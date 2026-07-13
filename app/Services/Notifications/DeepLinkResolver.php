<?php

namespace App\Services\Notifications;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\Supplier;
use App\Models\TreasuryTransaction;
use App\Models\UsedPhoneAcquisition;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns (reference_type, reference_id) into a clickable URL — the
 * Notification row itself never stores a URL. This is deliberate: it is what
 * lets the exact same stored row serve a web deep-link today via this
 * resolver, and a Flutter route later via the mobile client resolving
 * reference_type/reference_id itself. The mobile client never needs this
 * class or its web route names.
 *
 * Not every model has a per-record detail route yet in web.php — Expense is
 * a known, confirmed gap (only expenses.index / expenses.create exist, no
 * ExpenseDetail page). Unregistered or route-less types resolve to null and
 * the UI simply renders the notification without a clickable action rather
 * than guessing at a route that doesn't exist.
 */
class DeepLinkResolver
{
    public function resolve(?Model $reference): ?string
    {
        if (! $reference || ! $reference->exists) {
            return null;
        }

        return match (true) {
            $reference instanceof Sale => route('sales.show', $reference),
            $reference instanceof CreditNote => route('documents.credit-note', $reference),
            $reference instanceof Purchase => route('purchases.show', $reference),
            $reference instanceof TreasuryTransaction => route('treasury.show', $reference),
            $reference instanceof Customer => route('customers.show', $reference),
            $reference instanceof Supplier => route('suppliers.show', $reference),
            $reference instanceof ServiceTicket => route('service.show', $reference),
            $reference instanceof UsedPhoneAcquisition => route('used-phones.show', $reference),
            // No per-record route exists for Expense today — fall back to the
            // list rather than inventing 'expenses.show'. Wire this up once
            // an ExpenseDetail page exists.
            $reference instanceof Expense => route('expenses.index'),
            default => null,
        };
    }
}