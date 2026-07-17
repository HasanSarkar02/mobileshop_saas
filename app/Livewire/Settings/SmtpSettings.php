<?php

namespace App\Livewire\Settings;

use App\Actions\SendTestEmailAction;
use App\Enums\PermissionEnum;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Email (SMTP) Settings')]
class SmtpSettings extends Component
{
    use HasAuthorization;

    public bool $smtp_enabled = false;
    public string $smtp_host = '';
    public ?int $smtp_port = 587;
    public string $smtp_encryption = 'tls';
    public string $smtp_username = '';
    public string $smtp_password = '';
    public string $smtp_from_address = '';
    public string $smtp_from_name = '';

    public string $testEmailAddress = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionEnum::NotificationsManageSettings->value);

        $shop = Auth::user()->shop;

        $this->smtp_enabled = (bool) $shop->smtp_enabled;
        $this->smtp_host = $shop->smtp_host ?? '';
        $this->smtp_port = $shop->smtp_port ?? 587;
        $this->smtp_encryption = $shop->smtp_encryption ?? 'tls';
        $this->smtp_username = $shop->smtp_username ?? '';
        // Password intentionally left blank — never echo a stored secret back
        // to the browser. Leaving it blank on save keeps the existing value.
        $this->smtp_from_address = $shop->smtp_from_address ?? '';
        $this->smtp_from_name = $shop->smtp_from_name ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email',
            'smtp_from_name' => 'nullable|string|max:255',
        ]);

        $shop = Auth::user()->shop;

        $data = [
            'smtp_enabled' => $this->smtp_enabled,
            'smtp_host' => $this->smtp_host ?: null,
            'smtp_port' => $this->smtp_port,
            'smtp_encryption' => $this->smtp_encryption ?: null,
            'smtp_username' => $this->smtp_username ?: null,
            'smtp_from_address' => $this->smtp_from_address ?: null,
            'smtp_from_name' => $this->smtp_from_name ?: null,
        ];

        if ($this->smtp_password !== '') {
            $data['smtp_password'] = $this->smtp_password;
        }

        $shop->update($data);
        $this->smtp_password = '';

        $this->dispatch('notify', ['type' => 'success', 'message' => 'SMTP settings saved.']);
    }

    public function sendTestEmail(SendTestEmailAction $action): void
    {
        $this->validate(['testEmailAddress' => 'required|email']);

        $shop = Auth::user()->shop->fresh();
        $result = $action->execute($shop, $this->testEmailAddress);

        $this->dispatch('notify', ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']]);
    }

    public function render()
    {
        return view('livewire.settings.smtp-settings');
    }
}