<div class="max-w-xl mx-auto">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">
                {{ $employee?->exists ? 'Edit Employee' : 'Add Employee' }}
            </h2>
            @if (!$employee?->exists)
                <p class="text-xs text-gray-400 mt-0.5">
                    An email invite will be sent — employee sets their own password.
                </p>
            @endif
        </div>
        <form wire:submit="save" class="p-6 space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Full Name *</label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                        placeholder="Employee name">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Email *
                        @if ($employee?->exists)
                            <span class="text-xs font-normal text-gray-400">(cannot change)</span>
                        @endif
                    </label>
                    <input wire:model="email" type="email"
                        class="input @error('email') input-error @enderror {{ $employee?->exists ? 'bg-gray-50' : '' }}"
                        {{ $employee?->exists ? 'readonly' : '' }}>
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input wire:model="phone" type="tel" class="input" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="label">Role *</label>
                    <select wire:model="role" class="input @error('role') input-error @enderror">
                        <option value="">Select role…</option>
                        @foreach ($this->roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Branch
                        <span class="text-xs font-normal text-gray-400">— blank = access to all branches</span>
                    </label>
                    <select wire:model="branchId" class="input">
                        <option value="0">All branches</option>
                        @foreach ($this->branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (!$employee?->exists)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800">
                    📧 An invite email will be sent to <strong>{{ $email ?: '...' }}</strong>.
                    The employee must click the link and set a password to activate their account.
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $employee?->exists ? 'Update Employee' : 'Send Invite' }}
                    </span>
                    <span wire:loading>{{ $employee?->exists ? 'Saving…' : 'Sending…' }}</span>
                </button>
                <a href="{{ route('employees.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
