<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class SaleReceiptController extends Controller
{
    public function show(Sale $sale)
    {
        // Security: sale must belong to this user's shop
        if ($sale->shop_id !== Auth::user()->shop_id && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $sale->load([
            'shop',
            'branch',
            'cashier',
            'customer',
            'items',
            'payments.paymentAccount',
            'payments.financePartner',
            'financePartnerReceivable.financePartner',
        ]);

        return view('sales.receipt', compact('sale'));
    }
}