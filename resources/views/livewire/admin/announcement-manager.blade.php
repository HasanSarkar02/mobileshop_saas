<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Announcements</h2>
        <button wire:click="openCreate" class="btn-primary">+ New Announcement</button>
    </div>

    @if ($showForm)
        <div class="card p-6 border-2 border-indigo-200 space-y-4">
            <h3 class="font-semibold text-gray-900">{{ $editingId ? 'Edit Announcement' : 'New Announcement' }}</h3>

            <div>
                <label class="label text-xs">Title *</label>
                <input wire:model="title" type="text" class="input @error('title') input-error @enderror">
                @error('title')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Message *</label>
                <textarea wire:model="body" rows="3" class="input @error('body') input-error @enderror"></textarea>
                @error('body')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="label text-xs">Type</label>
                    <select wire:model="type" class="input">
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="label text-xs">Show To</label>
                    <select wire:model="audience" class="input">
                        <option value="both">Everyone (Shops + Admin Panel)</option>
                        <option value="shop_app">Shop App Users Only</option>
                        <option value="admin_panel">Super Admins Only</option>
                    </select>
                </div>
                <div class="flex items-end gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input wire:model="isActive" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        Active
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input wire:model="dismissible" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        Dismissible
                    </label>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label text-xs">Starts At <span class="text-gray-400">(optional)</span></label>
                    <input wire:model="startsAt" type="datetime-local" class="input">
                </div>
                <div>
                    <label class="label text-xs">Ends At <span class="text-gray-400">(optional)</span></label>
                    <input wire:model="endsAt" type="datetime-local"
                        class="input @error('endsAt') input-error @enderror">
                    @error('endsAt')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="save" class="btn-primary btn-sm">{{ $editingId ? 'Update' : 'Publish' }}</button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="table-th">Title</th>
                    <th class="table-th">Type</th>
                    <th class="table-th">Audience</th>
                    <th class="table-th">Window</th>
                    <th class="table-th">Status</th>
                    <th class="table-th">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->announcements as $a)
                    <tr wire:key="ann-{{ $a->id }}">
                        <td class="table-td">
                            <div class="font-medium text-gray-900">{{ $a->title }}</div>
                            <div class="text-xs text-gray-400 max-w-sm truncate">{{ $a->body }}</div>
                        </td>
                        <td class="table-td">
                            <span
                                class="badge text-xs {{ match ($a->type) {'critical' => 'badge-red','warning' => 'badge-yellow',default => 'badge-blue'} }}">
                                {{ ucfirst($a->type) }}
                            </span>
                        </td>
                        <td class="table-td text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($a->audience)) }}
                        </td>
                        <td class="table-td text-xs text-gray-500">
                            {{ $a->starts_at?->format('d M') ?? 'Now' }} →
                            {{ $a->ends_at?->format('d M Y') ?? 'No end' }}
                        </td>
                        <td class="table-td">
                            <button wire:click="toggleActive({{ $a->id }})"
                                class="badge text-xs {{ $a->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $a->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="table-td">
                            <div class="flex gap-3">
                                <button wire:click="openEdit({{ $a->id }})"
                                    class="text-xs text-indigo-600 hover:underline font-medium">Edit</button>
                                <button wire:click="delete({{ $a->id }})"
                                    wire:confirm="Delete this announcement?"
                                    class="text-xs text-red-500 hover:underline font-medium">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="table-td text-center text-gray-400 py-10">No announcements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
