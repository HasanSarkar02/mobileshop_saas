<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Departments</h2>
            <p class="text-sm text-gray-500 mt-0.5">Organise employees by department for payroll grouping.</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ New Department</button>
    </div>

    {{-- Form --}}
    @if($showForm)
        <div class="card p-6 border-indigo-200 border-2 space-y-4">
            <h3 class="font-semibold text-gray-900">
                {{ $editingId ? 'Edit Department' : 'New Department' }}
            </h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Department Name *</label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                        placeholder="e.g. Sales & Marketing">
                    @error('name')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Code <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <input wire:model="code" type="text" class="input @error('code') input-error @enderror"
                        placeholder="e.g. SALES" style="text-transform:uppercase">
                    @error('code')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Parent Department <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <select wire:model="parentId" class="input">
                        <option value="0">No parent (top-level)</option>
                        @foreach($this->departments as $dept)
                            @if($dept->id !== $editingId)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Department Head <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                    <select wire:model="headUserId" class="input">
                        <option value="0">Not assigned</option>
                        @foreach($this->employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description</label>
                    <textarea wire:model="description" rows="2" class="input"
                        placeholder="Optional description…"></textarea>
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="save" class="btn-primary btn-sm"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId ? 'Update Department' : 'Create Department' }}
                    </span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                <button wire:click="$set('showForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Department List --}}
    <div class="card overflow-hidden">
        @if($this->departments->isEmpty())
            <div class="p-10 text-center text-gray-400">
                No departments yet.
                <button wire:click="openCreate" class="text-indigo-600 hover:underline ml-1">
                    Create your first department →
                </button>
            </div>
        @else
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200"><tr>
                    <th class="table-th">Department</th>
                    <th class="table-th">Code</th>
                    <th class="table-th">Parent</th>
                    <th class="table-th">Head</th>
                    <th class="table-th text-center">Employees</th>
                    <th class="table-th">Status</th>
                    <th class="table-th">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($this->departments as $dept)
                        <tr class="hover:bg-gray-50 {{ !$dept->is_active ? 'opacity-50' : '' }}"
                            wire:key="dept-{{ $dept->id }}">
                            <td class="table-td font-semibold text-gray-900">
                                @if($dept->parent_department_id)
                                    <span class="text-gray-300 mr-1">└</span>
                                @endif
                                {{ $dept->name }}
                            </td>
                            <td class="table-td font-mono text-xs text-gray-500">
                                {{ $dept->code ?? '—' }}
                            </td>
                            <td class="table-td text-gray-500 text-sm">
                                {{ $dept->parent?->name ?? '—' }}
                            </td>
                            <td class="table-td text-gray-500 text-sm">
                                {{ $dept->head?->name ?? '—' }}
                            </td>
                            <td class="table-td text-center">
                                <span class="badge badge-blue">{{ $dept->salary_structures_count }}</span>
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $dept->is_active ? 'badge-green' : 'badge-gray' }} text-xs">
                                    {{ $dept->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <button wire:click="openEdit({{ $dept->id }})"
                                        class="text-xs text-indigo-600 hover:underline font-medium">
                                        Edit
                                    </button>
                                    <button wire:click="toggleActive({{ $dept->id }})"
                                        class="text-xs {{ $dept->is_active ? 'text-amber-500' : 'text-green-500' }} hover:underline font-medium">
                                        {{ $dept->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    @if($dept->salary_structures_count === 0)
                                        <button wire:click="delete({{ $dept->id }})"
                                            wire:confirm="Delete '{{ $dept->name }}'?"
                                            class="text-xs text-red-400 hover:underline font-medium">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>