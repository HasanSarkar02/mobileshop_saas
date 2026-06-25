<?php

namespace App\Livewire\UsedPhones;

use App\Actions\RecordUsedPhoneAcquisitionAction;
use App\Enums\PhoneCondition;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buy Used Phone')]
class RecordUsedPhone extends Component
{
    // Seller
    public string $sellerName    = '';
    public string $sellerPhone   = '';
    public string $sellerNid     = '';
    public string $sellerAddress = '';

    // Phone details
    public string $imei1           = '';
    public string $imei2           = '';
    public string $modelDescription = '';
    public int    $productVariantId = 0;
    public string $condition        = 'good';
    public string $conditionNotes   = '';
    public string $accessories      = '';

    // Financial
    public string $purchasePrice      = '';
    public string $expectedSellPrice  = '';
    public int    $paymentAccountId   = 0;
    public int    $branchId           = 0;
    public string $notes              = '';

    // Product search
    public string $variantSearch   = '';
    public array  $variantResults  = [];
    public bool   $showVariantDrop = false;
    public string $selectedVariantLabel = '';

    public function mount(): void
    {
        $this->branchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get();
    }

    public function updatedVariantSearch(): void
    {
        if (strlen(trim($this->variantSearch)) < 2) {
            $this->variantResults = [];
            $this->showVariantDrop = false;
            return;
        }

        $shopId = Auth::user()->shop_id;
        $q      = $this->variantSearch;

        $this->variantResults = ProductVariant::with('product.brand')
            ->whereHas('product', fn($pq) =>
                $pq->where('shop_id', $shopId)
                   ->where('tracking_type', 'serialized')
                   ->where('name', 'like', "%{$q}%")
            )
            ->where('is_active', true)
            ->limit(8)
            ->get()
            ->map(fn ($v) => [
                'id'    => $v->id,
                'label' => trim(($v->product->brand?->name ?? '') . ' ' . $v->product->name .
                          ($v->attributes_label ? ' — '.$v->attributes_label : '')),
                'sku'   => $v->sku,
                'price' => $v->selling_price,
            ])
            ->toArray();

        $this->showVariantDrop = ! empty($this->variantResults);
    }

    public function selectVariant(int $id, string $label, float $price): void
    {
        $this->productVariantId      = $id;
        $this->selectedVariantLabel  = $label;
        $this->variantSearch         = $label;
        $this->showVariantDrop       = false;

        if (empty($this->expectedSellPrice)) {
            $this->expectedSellPrice = (string) $price;
        }

        if (empty($this->modelDescription)) {
            $this->modelDescription = $label;
        }
    }

    public function clearVariant(): void
    {
        $this->productVariantId     = 0;
        $this->selectedVariantLabel = '';
        $this->variantSearch        = '';
        $this->showVariantDrop      = false;
    }

    public function save(RecordUsedPhoneAcquisitionAction $action): void
    {
        $this->validate([
            'sellerName'       => 'required|string|max:255',
            'sellerPhone'      => 'nullable|string|max:20',
            'imei1'            => 'required|digits_between:14,15',
            'modelDescription' => 'required|string|max:255',
            'condition'        => 'required|in:'.implode(',', array_column(PhoneCondition::cases(), 'value')),
            'purchasePrice'    => 'required|numeric|min:1',
            'paymentAccountId' => 'required|integer|min:1',
            'branchId'         => 'required|integer|min:1',
        ], [
            'imei1.digits_between'  => 'IMEI must be 14–15 digits.',
            'paymentAccountId.min'  => 'Please select a payment account.',
        ]);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $acquisition = $action->execute($shop, [
                'branch_id'          => $this->branchId,
                'seller_name'        => $this->sellerName,
                'seller_phone'       => $this->sellerPhone ?: null,
                'seller_nid'         => $this->sellerNid ?: null,
                'seller_address'     => $this->sellerAddress ?: null,
                'imei_1'             => $this->imei1,
                'imei_2'             => $this->imei2 ?: null,
                'model_description'  => $this->modelDescription,
                'product_variant_id' => $this->productVariantId ?: null,
                'condition'          => $this->condition,
                'condition_notes'    => $this->conditionNotes ?: null,
                'accessories'        => $this->accessories ?: null,
                'purchase_price'     => (float) $this->purchasePrice,
                'expected_sell_price'=> (float) $this->expectedSellPrice ?: 0,
                'payment_account_id' => $this->paymentAccountId,
                'notes'              => $this->notes ?: null,
            ], Auth::user());

            $this->dispatch('notify', type: 'success',
                message: "{$acquisition->acquisition_number} — {$this->modelDescription} added to inventory.");

            $this->redirect(route('used-phones.index'), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.used-phones.record-used-phone');
    }
}