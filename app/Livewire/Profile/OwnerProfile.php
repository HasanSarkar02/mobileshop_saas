<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('My Profile')]
class OwnerProfile extends Component
{
    // Info
    public string $name  = '';
    public string $email = '';
    public string $phone = '';

    // Password
    public string $currentPassword = '';
    public string $newPassword     = '';
    public string $confirmPassword = '';

    public bool $showPasswordForm = false;

    public function mount(): void
    {
        $user        = Auth::user();
        $this->name  = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    public string $passwordForEmail = '';
    public bool   $changingEmail    = false;

    public function saveInfo(): void
    {
        $user = Auth::user();

        $this->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $updates = ['name' => $this->name, 'phone' => $this->phone ?: null];

        // Email changed — require password confirmation
        if ($this->email !== $user->email) {
            $this->validate([
                'email'            => ['required','email', Rule::unique('users','email')->ignore($user->id)],
                'passwordForEmail' => 'required|string',
            ], ['passwordForEmail.required' => 'Enter your password to confirm email change.']);

            if (! \Illuminate\Support\Facades\Hash::check($this->passwordForEmail, $user->password)) {
                $this->addError('passwordForEmail', 'Password is incorrect.');
                return;
            }

            $updates['email']          = $this->email;
            $this->passwordForEmail    = '';
            $this->changingEmail       = false;
        }

        $user->update($updates);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Profile updated.']);
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required|string',
            'newPassword'     => 'required|string|min:8|confirmed',
        ], [
            'newPassword.confirmed' => 'New password and confirmation do not match.',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update([
            'password'            => Hash::make($this->newPassword),
            'password_changed_at' => now(),
        ]);

        $this->currentPassword = '';
        $this->newPassword     = '';
        $this->confirmPassword = '';
        $this->showPasswordForm = false;

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Password changed successfully.']);
    }

    public function render()
    {
        return view('livewire.profile.owner-profile', [
            'user' => Auth::user()->load('shop', 'branch'),
        ]);
    }
}