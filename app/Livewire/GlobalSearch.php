<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string  $query       = '';
    public bool    $open        = false;
    public int     $cursor      = 0;   // keyboard nav index

    public function updatedQuery(): void
    {
        $this->cursor = 0;
    }

    public function open(): void  { $this->open = true; }
    public function close(): void { $this->open = false; $this->query = ''; $this->cursor = 0; }

    public function moveCursor(int $direction): void
    {
        $total = collect($this->results)->flatten(1)->count();
        if ($total === 0) return;
        $this->cursor = ($this->cursor + $direction + $total) % $total;
    }

    #[Computed]
    public function results(): array
    {
        $q      = trim($this->query);
        $user   = Auth::user();
        $shopId = $user->shop_id;

        if (strlen($q) < 2) return [];

        $groups = [];

        // ── Customers ────────────────────────────────────────────────────────
        if ($user->can('customers.view')) {
            $rows = Customer::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('customer_type', '!=', 'walk_in')
                ->where(fn ($sq) =>
                    $sq->where('name', 'like', "{$q}%")
                       ->orWhere('phone', 'like', "{$q}%")
                )
                ->select('id', 'name', 'phone', 'current_balance')
                ->limit(5)->get()
                ->map(fn ($r) => [
                    'type'     => 'Customer',
                    'icon'     => '👤',
                    'label'    => $r->name,
                    'sub'      => $r->phone,
                    'badge'    => $r->current_balance > 0 ? 'Due: ৳'.number_format($r->current_balance, 0) : null,
                    'url'      => route('customers.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Customers'] = $rows->toArray();
        }

        // ── Sales ─────────────────────────────────────────────────────────────
        if ($user->can('sales.view')) {
            $rows = Sale::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('sale_number', 'like', "{$q}%")
                ->select('id', 'sale_number', 'grand_total', 'status', 'confirmed_at')
                ->limit(5)->get()
                ->map(fn ($r) => [
                    'type'  => 'Sale',
                    'icon'  => '🧾',
                    'label' => $r->sale_number,
                    'sub'   => $r->confirmed_at?->format('d M Y'),
                    'badge' => '৳'.number_format($r->grand_total, 0),
                    'url'   => route('sales.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Sales'] = $rows->toArray();
        }

        // ── Products ──────────────────────────────────────────────────────────
        if ($user->can('inventory.view')) {
            $rows = Product::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('name', 'like', "%{$q}%")
                ->where('is_active', true)
                ->select('id', 'name', 'tracking_type')
                ->limit(5)->get()
                ->map(fn ($r) => [
                    'type'  => 'Product',
                    'icon'  => '📦',
                    'label' => $r->name,
                    'sub'   => ucfirst(str_replace('_', ' ', $r->tracking_type->value)),
                    'badge' => null,
                    'url'   => route('products.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Products'] = $rows->toArray();
        }

        // ── IMEI / Serial ─────────────────────────────────────────────────────
        if ($user->can('inventory.view') && strlen($q) >= 5) {
            $rows = ProductUnit::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where(fn ($sq) =>
                    $sq->where('serial_number', 'like', "{$q}%")
                       ->orWhere('secondary_serial_number', 'like', "{$q}%")
                )
                ->with('variant.product')
                ->select('id', 'serial_number', 'status', 'product_variant_id')
                ->limit(5)->get()
                ->map(fn ($r) => [
                    'type'  => 'IMEI',
                    'icon'  => '📱',
                    'label' => $r->serial_number,
                    'sub'   => $r->variant?->product?->name,
                    'badge' => ucfirst(str_replace('_', ' ', $r->status->value)),
                    'url'   => route('products.show', $r->variant?->product_id ?? 0),
                ]);
            if ($rows->isNotEmpty()) $groups['IMEI / Serial'] = $rows->toArray();
        }

        // ── Purchases ─────────────────────────────────────────────────────────
        if ($user->can('purchases.view')) {
            $rows = Purchase::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('reference_number', 'like', "{$q}%")
                ->select('id', 'reference_number', 'total_amount', 'purchase_date')
                ->limit(3)->get()
                ->map(fn ($r) => [
                    'type'  => 'Purchase',
                    'icon'  => '🛒',
                    'label' => $r->reference_number,
                    'sub'   => $r->purchase_date->format('d M Y'),
                    'badge' => '৳'.number_format($r->total_amount, 0),
                    'url'   => route('purchases.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Purchases'] = $rows->toArray();
        }

        // ── Suppliers ─────────────────────────────────────────────────────────
        if ($user->can('suppliers.view')) {
            $rows = Supplier::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where(fn ($sq) =>
                    $sq->where('name', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "{$q}%")
                )
                ->select('id', 'name', 'phone', 'current_balance')
                ->limit(3)->get()
                ->map(fn ($r) => [
                    'type'  => 'Supplier',
                    'icon'  => '🤝',
                    'label' => $r->name,
                    'sub'   => $r->phone,
                    'badge' => $r->current_balance > 0 ? 'Due: ৳'.number_format($r->current_balance, 0) : null,
                    'url'   => route('suppliers.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Suppliers'] = $rows->toArray();
        }

        // ── Service Tickets ───────────────────────────────────────────────────
        if ($user->can('service.view') && class_exists(ServiceTicket::class)) {
            $rows = ServiceTicket::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where(fn ($sq) =>
                    $sq->where('ticket_number', 'like', "{$q}%")
                       ->orWhere('customer_name', 'like', "%{$q}%")
                       ->orWhere('customer_phone', 'like', "{$q}%")
                )
                ->select('id', 'ticket_number', 'customer_name', 'status')
                ->limit(3)->get()
                ->map(fn ($r) => [
                    'type'  => 'Service',
                    'icon'  => '🔧',
                    'label' => $r->ticket_number,
                    'sub'   => $r->customer_name,
                    'badge' => ucfirst($r->status?->value),
                    'url'   => route('service.show', $r->id),
                ]);
            if ($rows->isNotEmpty()) $groups['Service'] = $rows->toArray();
        }

        return $groups;
    }

    #[Computed]
    public function flatResults(): array
    {
        return collect($this->results)->flatten(1)->values()->toArray();
    }

    public function goToCursor(): void
    {
        $flat = $this->flatResults;
        if (isset($flat[$this->cursor])) {
            $this->redirect($flat[$this->cursor]['url'], navigate: true);
            $this->close();
        }
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}