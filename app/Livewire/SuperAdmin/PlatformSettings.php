<?php

namespace App\Livewire\SuperAdmin;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
#[Title('Platform Settings')]
class PlatformSettings extends Component
{
    use WithFileUploads;

    public string $appName = '';
    public string $supportEmail = '';
    public string $supportPhone = '';
    public string $defaultCurrency = 'BDT';
    public string $defaultTimezone = 'Asia/Dhaka';
    public int $defaultTrialDays = 14;
    public string $termsUrl = '';
    public string $privacyUrl = '';
    public string $footerText = '';

    public $logoUpload = null;
    public $faviconUpload = null;

    public ?string $currentLogoPath = null;
    public ?string $currentFaviconPath = null;

    // Only populated when toggled from this screen — the bypass secret
    // isn't retrievable once generated, so it's shown once and then gone.
    public ?string $maintenanceSecret = null;
    public ?string $maintenanceBypassUrl = null;

    public function mount(): void
    {
        $settings = PlatformSetting::current();

        $this->appName            = $settings->app_name;
        $this->supportEmail       = $settings->support_email ?? '';
        $this->supportPhone       = $settings->support_phone ?? '';
        $this->defaultCurrency    = $settings->default_currency;
        $this->defaultTimezone    = $settings->default_timezone;
        $this->defaultTrialDays   = $settings->default_trial_days;
        $this->termsUrl           = $settings->terms_url ?? '';
        $this->privacyUrl         = $settings->privacy_url ?? '';
        $this->footerText         = $settings->footer_text ?? '';
        $this->currentLogoPath    = $settings->logo_path;
        $this->currentFaviconPath = $settings->favicon_path;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'appName'          => 'required|string|max:100',
            'supportEmail'     => 'nullable|email|max:255',
            'supportPhone'     => 'nullable|string|max:30',
            'defaultCurrency'  => 'required|string|size:3',
            'defaultTimezone'  => 'required|string|max:50',
            'defaultTrialDays' => 'required|integer|min:1|max:90',
            'termsUrl'         => 'nullable|url|max:255',
            'privacyUrl'       => 'nullable|url|max:255',
            'footerText'       => 'nullable|string|max:255',
        ]);

        $settings = PlatformSetting::current();

        if ($this->logoUpload) {
            $this->validate(['logoUpload' => 'image|max:2048']);
            $settings->logo_path = $this->logoUpload->store('platform', 'public');
        }

        if ($this->faviconUpload) {
            $this->validate(['faviconUpload' => 'image|max:512']);
            $settings->favicon_path = $this->faviconUpload->store('platform', 'public');
        }

        $settings->fill([
            'app_name'           => $validated['appName'],
            'support_email'      => $validated['supportEmail'] ?: null,
            'support_phone'      => $validated['supportPhone'] ?: null,
            'default_currency'   => strtoupper($validated['defaultCurrency']),
            'default_timezone'   => $validated['defaultTimezone'],
            'default_trial_days' => $validated['defaultTrialDays'],
            'terms_url'          => $validated['termsUrl'] ?: null,
            'privacy_url'        => $validated['privacyUrl'] ?: null,
            'footer_text'        => $validated['footerText'] ?: null,
        ])->save();

        PlatformSetting::clearCache();

        $this->currentLogoPath    = $settings->logo_path;
        $this->currentFaviconPath = $settings->favicon_path;
        $this->logoUpload         = null;
        $this->faviconUpload      = null;

        $this->dispatch('notify', type: 'success', message: 'Platform settings updated.');
    }

    public function isDownForMaintenance(): bool
    {
        return app()->isDownForMaintenance();
    }

    public function enableMaintenance(): void
    {
        $secret = Str::random(24);

        Artisan::call('down', ['--secret' => $secret]);

        $this->maintenanceSecret    = $secret;
        $this->maintenanceBypassUrl = url($secret);

        $this->dispatch('notify', type: 'warning', message: 'Maintenance mode enabled.');
    }

    public function disableMaintenance(): void
    {
        Artisan::call('up');

        $this->maintenanceSecret    = null;
        $this->maintenanceBypassUrl = null;

        $this->dispatch('notify', type: 'success', message: 'Maintenance mode disabled. Site is live.');
    }

    public function render()
    {
        return view('livewire.admin.platform-settings');
    }
}