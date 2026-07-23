<?php

namespace App\Livewire\SuperAdmin;

use App\Models\PlatformAnnouncement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Announcements')]
class AnnouncementManager extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $body = '';
    public string $type = 'info';
    public string $audience = 'both';
    public bool $isActive = true;
    public bool $dismissible = true;
    public string $startsAt = '';
    public string $endsAt = '';

    #[Computed]
    public function announcements()
    {
        return PlatformAnnouncement::with('creator')->latest()->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'body', 'startsAt', 'endsAt']);
        $this->type        = 'info';
        $this->audience     = 'both';
        $this->isActive     = true;
        $this->dismissible  = true;
        $this->showForm     = true;
    }

    public function openEdit(int $id): void
    {
        $a = PlatformAnnouncement::findOrFail($id);
        $this->editingId   = $id;
        $this->title       = $a->title;
        $this->body        = $a->body;
        $this->type        = $a->type;
        $this->audience    = $a->audience;
        $this->isActive    = $a->is_active;
        $this->dismissible = $a->dismissible;
        $this->startsAt    = $a->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt      = $a->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->showForm    = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title'    => 'required|string|max:150',
            'body'     => 'required|string|max:1000',
            'type'     => 'required|in:info,warning,critical',
            'audience' => 'required|in:shop_app,admin_panel,both',
            'startsAt' => 'nullable|date',
            'endsAt'   => 'nullable|date|after_or_equal:startsAt',
        ]);

        $data = [
            'title'       => $validated['title'],
            'body'        => $validated['body'],
            'type'        => $validated['type'],
            'audience'    => $validated['audience'],
            'is_active'   => $this->isActive,
            'dismissible' => $this->dismissible,
            'starts_at'   => $this->startsAt ?: null,
            'ends_at'     => $this->endsAt ?: null,
        ];

        if ($this->editingId) {
            PlatformAnnouncement::findOrFail($this->editingId)->update($data);
            $msg = 'Announcement updated.';
        } else {
            $data['created_by'] = Auth::guard('admin')->id();
            PlatformAnnouncement::create($data);
            $msg = 'Announcement created.';
        }

        $this->showForm = false;
        unset($this->announcements);
        $this->dispatch('notify', type: 'success', message: $msg);
    }

    public function toggleActive(int $id): void
    {
        $a = PlatformAnnouncement::findOrFail($id);
        $a->update(['is_active' => ! $a->is_active]);
        unset($this->announcements);
    }

    public function delete(int $id): void
    {
        PlatformAnnouncement::findOrFail($id)->delete();
        unset($this->announcements);
        $this->dispatch('notify', type: 'warning', message: 'Announcement deleted.');
    }

    public function render()
    {
        return view('livewire.admin.announcement-manager');
    }
}