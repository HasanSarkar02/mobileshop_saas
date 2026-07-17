<div class="space-y-4">

    {{-- Stats --}}
    @php $s = $this->stats; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-2xl font-bold text-indigo-700">{{ $s['total'] }}</div>
            <div class="text-xs font-medium text-indigo-500 mt-0.5">Total Employees</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-2xl font-bold text-green-700">{{ $s['active'] }}</div>
            <div class="text-xs font-medium text-green-500 mt-0.5">Active</div>
        </div>
        <div class="card p-4 border-0 bg-gray-50">
            <div class="text-2xl font-bold text-gray-500">{{ $s['inactive'] }}</div>
            <div class="text-xs font-medium text-gray-400 mt-0.5">Inactive</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Name, email, phone…"
            class="input max-w-xs">
        <select wire:model.live="branchFilter" class="input w-auto">
            <option value="">All branches</option>
            <option value="any">No branch assigned</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>

        @can('employees.manage')
            <button wire:click="$toggle('showLocalEmpForm')" class="btn-secondary btn-sm">
                + Add Local Employee (No Account)
            </button>
            <a href="{{ route('employees.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
                + Add Employee
            </a>
        @endcan
    </div>
    <div wire:show="showLocalEmpForm" class="card p-5 border-amber-200 bg-amber-50">
        <h4 class="font-semibold text-amber-900 mb-3">Add Local Employee</h4>
        <p class="text-xs text-amber-700 mb-3">
            This employee will appear in payroll but cannot log into the system.
            Use for cashiers, helpers, or staff without a computer.
        </p>
        <div class="flex gap-3">
            <div class="flex-1">
                <label class="label text-xs">Name *</label>
                <input wire:model="localEmpName" type="text" class="input text-sm" placeholder="Employee name">
                @error('localEmpName')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="w-44">
                <label class="label text-xs">Phone</label>
                <input wire:model="localEmpPhone" type="tel" class="input text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button wire:click="createLocalEmployee" class="btn-success btn-sm">Add</button>
                <button wire:click="$set('showLocalEmpForm', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Employee</th>
                        <th class="table-th">Role</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th">Salary</th>
                        <th class="table-th">Last Login</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-gray-50 {{ !$emp->is_active ? 'opacity-60' : '' }}">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-bold text-indigo-600">
                                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">{{ $emp->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $emp->email }}</div>
                                        @if ($emp->phone)
                                            <div class="text-xs text-gray-400">{{ $emp->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                @if ($emp->roles->isNotEmpty())
                                    <span class="badge badge-blue text-xs">{{ $emp->roles->first()->name }}</span>
                                @else
                                    <span class="text-gray-300 text-xs">No role</span>
                                @endif
                            </td>
                            <td class="table-td text-gray-500 text-sm">
                                {{ $emp->branch?->name ?? 'All branches' }}
                            </td>
                            <td class="table-td text-gray-700 text-sm">
                                @php
                                    $sal = $emp->relationLoaded('activeSalaryStructure')
                                        ? $emp->activeSalaryStructure
                                        : null;
                                @endphp

                                {{ $sal?->policy?->name ?? ($emp->employeeProfile?->designation ?? '—') }}
                            </td>
                            <td class="table-td text-gray-400 text-xs">
                                {{ $emp->last_login_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="table-td">
                                @if (!$emp->password_changed_at)
                                    <span class="badge badge-yellow">Invite Pending</span>
                                @elseif($emp->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Inactive</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('employees.show', $emp) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">
                                        View
                                    </a>
                                    @can('employees.manage')
                                        <a href="{{ route('employees.edit', $emp) }}" wire:navigate
                                            class="text-xs text-gray-500 hover:underline font-medium">
                                            Edit
                                        </a>
                                        <button wire:click="toggleActive({{ $emp->id }})"
                                            class="text-xs {{ $emp->is_active ? 'text-red-500' : 'text-green-600' }} hover:underline font-medium">
                                            {{ $emp->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-12">
                                No employees yet.
                                @can('employees.manage')
                                    <a href="{{ route('employees.create') }}" wire:navigate
                                        class="text-indigo-600 hover:underline">Add one</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $employees->links() }}</div>
        @endif
    </div>
</div>
