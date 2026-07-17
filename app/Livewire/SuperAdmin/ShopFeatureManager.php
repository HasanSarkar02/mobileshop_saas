<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\ShopFeature;
use App\Models\Shop;
use App\Services\ShopFeatureService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Shop Features')]
class ShopFeatureManager extends Component
{
    public Shop   $shop;
    public array  $selectedFeatures = [];
    public bool   $allEnabled       = false;

    public function mount(Shop $shop): void
    {
        $this->shop = $shop->withoutGlobalScopes()->findOrFail($shop->id);

        $enabled = $this->shop->enabled_features;

        if ($enabled === null) {
            $this->allEnabled       = true;
            $this->selectedFeatures = array_column(ShopFeature::cases(), 'value');
        } else {
            $this->allEnabled       = false;
            $this->selectedFeatures = $enabled;
        }
    }

    public function toggleAll(): void
    {
        if ($this->allEnabled) {
            $this->selectedFeatures = array_column(ShopFeature::cases(), 'value');
        } else {
            $this->selectedFeatures = [];
        }
    }

    public function save(ShopFeatureService $service): void
    {
        $features = $this->allEnabled ? null : array_values($this->selectedFeatures);

        $service->setFeatures($this->shop->id, $features);

        $this->dispatch('notify', ['type' => 'success',
            'message' => "Features updated for {$this->shop->name}."]);
    }

    public function allFeatures(): array
    {
        return ShopFeature::cases();
    }

    public function render()
    {
        return view('livewire.admin.shop-feature-manager');
    }
}