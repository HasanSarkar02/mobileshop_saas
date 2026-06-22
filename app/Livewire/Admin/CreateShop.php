<?php

namespace App\Livewire\Admin;

use App\Actions\CreateShopAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Create Shop')]
class CreateShop extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $ownerName = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    #[Validate('required|in:mobile_shop,electronics,general_retail')]
    public string $businessType = 'mobile_shop';

    #[Validate('required|integer|min:1|max:90')]
    public int $trialDays = 14;

    public bool $vatEnabled = false;

    #[Validate('nullable|string|max:100')]
    public string $vatRegistrationNumber = '';

    #[Validate('nullable|numeric|min:0|max:100')]
    public string $defaultVatRate = '0';

    public bool $success = false;
    public string $successMessage = '';

    public function save(CreateShopAction $action): void
    {
        $this->validate();

        $result = $action->execute([
            'name' => $this->name,
            'owner_name' => $this->ownerName,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'business_type' => $this->businessType,
            'trial_days' => $this->trialDays,
            'vat_enabled' => $this->vatEnabled,
            'vat_registration_number' => $this->vatRegistrationNumber ?: null,
            'default_vat_rate' => $this->vatEnabled ? (float) $this->defaultVatRate : 0,
        ]);

        $this->successMessage = "Shop \"{$result['shop']->name}\" created. Invite email sent to {$this->email}.";
        $this->success = true;
        $this->dispatch('notify', type: 'success', message: $this->successMessage);
    }

    public function createAnother(): void
    {
        $this->reset(['name', 'ownerName', 'email', 'phone', 'address', 'vatEnabled', 'vatRegistrationNumber', 'defaultVatRate', 'success', 'successMessage']);
        $this->businessType = 'mobile_shop';
        $this->trialDays = 14;
    }

    public function render()
    {
        return view('livewire.admin.create-shop');
    }
}