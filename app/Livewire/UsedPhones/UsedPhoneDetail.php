<?php

namespace App\Livewire\UsedPhones;

use App\Models\SaleItem;
use App\Models\UsedPhoneAcquisition;
use App\Services\Media\ImageUploadService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Used Phone Detail')]
class UsedPhoneDetail extends Component
{
    use \App\Traits\HasAuthorization;
    use WithFileUploads;

    public UsedPhoneAcquisition $acquisition;

    public bool $editingSeller = false;

    public string $sellerName    = '';
    public string $sellerPhone   = '';
    public string $sellerNid     = '';
    public string $sellerAddress = '';

    public $sellerPhoto;
    public $sellerNidFront;
    public $sellerNidBack;

    public function mount(UsedPhoneAcquisition $acquisition): void
    {
        $this->requirePermission('used_phones.view');

        $this->acquisition = $acquisition->load([
            'variant.product',
            'productUnit.branch',
            'paymentAccount',
            'branch',
            'createdBy',
            'tradeInSale.customer',
        ]);
    }

    public function startEditingSeller(): void
    {
        $this->requirePermission('used_phones.manage');

        $this->sellerName    = $this->acquisition->seller_name;
        $this->sellerPhone   = $this->acquisition->seller_phone ?? '';
        $this->sellerNid     = $this->acquisition->seller_nid ?? '';
        $this->sellerAddress = $this->acquisition->seller_address ?? '';
        $this->sellerPhoto = null;
        $this->sellerNidFront = null;
        $this->sellerNidBack = null;

        $this->resetErrorBag();
        $this->editingSeller = true;
    }

    public function cancelEditingSeller(): void
    {
        $this->editingSeller = false;
        $this->sellerPhoto = null;
        $this->sellerNidFront = null;
        $this->sellerNidBack = null;
        $this->resetErrorBag();
    }

    public function saveSellerInfo(ImageUploadService $imageService): void
    {
        $this->requirePermission('used_phones.manage');

        $this->validate([
            'sellerName'     => 'required|string|max:255',
            'sellerPhone'    => 'nullable|string|max:20',
            'sellerNid'      => 'nullable|string|max:255',
            'sellerAddress'  => 'nullable|string|max:1000',
            'sellerPhoto'    => 'nullable|image|max:15360|mimes:jpg,jpeg,png,webp',
            'sellerNidFront' => 'nullable|image|max:15360|mimes:jpg,jpeg,png,webp',
            'sellerNidBack'  => 'nullable|image|max:15360|mimes:jpg,jpeg,png,webp',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($imageService) {
            $dir = "shops/{$this->acquisition->shop_id}/used-phones";

            $data = [
                'seller_name'    => $this->sellerName,
                'seller_phone'   => $this->sellerPhone ?: null,
                'seller_nid'     => $this->sellerNid ?: null,
                'seller_address' => $this->sellerAddress ?: null,
            ];

            if ($this->sellerPhoto) {
                $data['seller_photo_path'] = $imageService->replace(
                    $this->sellerPhoto, $this->acquisition->seller_photo_path, $dir
                );
            }
            if ($this->sellerNidFront) {
                $data['seller_nid_front_path'] = $imageService->replace(
                    $this->sellerNidFront, $this->acquisition->seller_nid_front_path, $dir
                );
            }
            if ($this->sellerNidBack) {
                $data['seller_nid_back_path'] = $imageService->replace(
                    $this->sellerNidBack, $this->acquisition->seller_nid_back_path, $dir
                );
            }

            $this->acquisition->update($data);
        });

        $this->acquisition->refresh();
        $this->editingSeller = false;
        $this->sellerPhoto = null;
        $this->sellerNidFront = null;
        $this->sellerNidBack = null;

        $this->dispatch('notify', type: 'success', message: 'Seller information updated.');
    }

    #[On('stock-adjusted')]
    public function refreshUnit(): void
    {
        $this->acquisition->refresh();
        $this->acquisition->load('productUnit.branch');
    }

    #[Computed]
    public function saleRecord(): ?SaleItem
    {
        if (! $this->acquisition->product_unit_id) return null;

        return SaleItem::whereHas('sale', fn ($q) =>
            $q->where('status', 'confirmed')
        )
        ->where('product_unit_id', $this->acquisition->product_unit_id)
        ->with('sale.customer', 'sale.cashier')
        ->first();
    }

    #[Computed]
    public function profit(): ?float
    {
        $saleItem = $this->saleRecord;
        if (! $saleItem) return null;

        return (float) $saleItem->line_total - (float) $this->acquisition->purchase_price;
    }

    public function render()
    {
        return view('livewire.used-phones.used-phone-detail', [
            'acquisition' => $this->acquisition,
            'saleRecord'  => $this->saleRecord,
            'profit'      => $this->profit,
        ]);
    }
}