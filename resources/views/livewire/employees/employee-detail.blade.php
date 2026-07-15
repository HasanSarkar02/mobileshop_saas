<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
            <span class="text-2xl font-bold text-indigo-600">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </span>
        </div>
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $employee->name }}</h2>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <span class="text-sm text-gray-500">{{ $employee->email }}</span>
                @if ($employee->phone)
                    <span class="text-gray-300">·</span><span class="text-sm text-gray-500">{{ $employee->phone }}</span>
                @endif
                @if ($employee->roles->isNotEmpty())
                    <span class="badge badge-blue">{{ $employee->roles->first()->name }}</span>
                @endif
                @if ($employee->is_active)
                    <span class="badge badge-green">Active</span>
                @else
                    <span class="badge badge-red">Inactive</span>
                @endif
                @if (!$employee->password_changed_at)
                    <span class="badge badge-yellow">Invite Pending</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-4 text-xs text-gray-400 mt-1">
                <span>Branch: {{ $employee->branch?->name ?? 'All branches' }}</span>
                <span>Last Login: {{ $employee->last_login_at?->diffForHumans() ?? 'Never' }}</span>
                @if ($employee->employeeProfile?->designation)
                    <span>{{ $employee->employeeProfile->designation }}</span>
                @endif
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('payroll.salary.setup', $employee) }}" wire:navigate class="btn-secondary btn-sm">
                💰 Salary Setup
            </a>
            @if (!$employee->password_changed_at)
                <button wire:click="resendInvite" class="btn-secondary btn-sm">
                    📧 Resend Invite
                </button>
            @endif
            @can('employees.manage')
                <a href="{{ route('employees.edit', $employee) }}" wire:navigate class="btn-secondary btn-sm">
                    Edit
                </a>
            @endcan

        </div>
    </div>

    {{-- This Month Stats --}}
    @php $ms = $this->monthSales; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xl font-bold text-indigo-700">{{ $ms['count'] }}</div>
            <div class="text-xs font-medium text-indigo-500 mt-0.5">Sales this month</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xl font-bold text-green-700">৳{{ number_format($ms['revenue'], 0) }}</div>
            <div class="text-xs font-medium text-green-500 mt-0.5">Revenue this month</div>
        </div>
        <div class="card p-4 border-0 bg-blue-50">
            <div class="text-xl font-bold text-blue-700">৳{{ number_format($ms['profit'], 0) }}</div>
            <div class="text-xs font-medium text-blue-500 mt-0.5">Profit this month</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'overview', 'label' => 'Overview'], ['key' => 'permissions', 'label' => 'Roles & Permissions']] as $tab)
                <button wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Overview Tab --}}
        <div wire:show="activeTab === 'overview'" class="p-5 space-y-4">
            @if ($employee->employeeProfile)
                @php $p = $employee->employeeProfile; @endphp
                {{-- Salary Information --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-900 text-sm">Salary Structure</h3>
                        @can('payroll.manage_structure')
                            <a href="{{ route('payroll.salary.setup', $employee) }}" wire:navigate
                                class="text-xs text-indigo-600 hover:underline font-medium">
                                {{ $employee->activeSalaryStructure ? 'Edit Setup →' : '+ Setup Salary →' }}
                            </a>
                        @endcan
                    </div>

                    @if ($structure = $employee->activeSalaryStructure)
                        <div class="space-y-2">
                            @foreach ([['label' => 'Policy', 'value' => $structure->policy?->name], ['label' => 'Department', 'value' => $structure->department?->name ?? '—'], ['label' => 'Designation', 'value' => $structure->designation ?? '—'], ['label' => 'Employment Type', 'value' => $structure->employment_type?->label()], ['label' => 'Payment Via', 'value' => $structure->paymentAccount?->name ?? '—'], ['label' => 'Effective From', 'value' => $structure->effective_from?->format('d M Y')], ['label' => 'Working Days', 'value' => $structure->monthly_working_days . ' days/month']] as $row)
                                @if ($row['value'])
                                    <div class="flex gap-3 text-sm">
                                        <span class="text-gray-400 w-36 shrink-0">{{ $row['label'] }}</span>
                                        <span class="text-gray-800 font-medium">{{ $row['value'] }}</span>
                                    </div>
                                @endif
                            @endforeach

                            {{-- Component breakdown --}}
                            @php
                                $comps = \App\Models\EmployeeSalaryComponent::where(
                                    'salary_structure_id',
                                    $structure->id,
                                )
                                    ->with('component')
                                    ->get();
                            @endphp
                            @if ($comps->isNotEmpty())
                                <div class="border-t border-gray-100 pt-3 mt-3">
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                        Component Overrides
                                    </div>
                                    @foreach ($comps as $comp)
                                        <div class="flex justify-between text-sm py-0.5">
                                            <span class="text-gray-500">{{ $comp->component?->name }}</span>
                                            <span class="font-medium text-gray-800">
                                                @if ($comp->calculation_type === 'percentage')
                                                    {{ $comp->value }}% of {{ $comp->percentage_of }}
                                                @else
                                                    ৳{{ number_format($comp->value, 0) }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-amber-600 bg-amber-50 rounded-xl p-3">
                            ⚠ No salary structure configured.
                            @can('payroll.manage_structure')
                                <a href="{{ route('payroll.salary.setup', $employee) }}" wire:navigate
                                    class="underline ml-1">Set up now →</a>
                            @endcan
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-400">No salary profile configured yet.
                    <a href="{{ route('payroll.employees') }}" wire:navigate
                        class="text-indigo-600 hover:underline">Set up salary →</a>
                </p>
            @endif
        </div>

        {{-- Permissions Tab --}}
        <div wire:show="activeTab === 'permissions'" class="p-5">
            @cannot('employees.manage_permissions')
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    ⚠ You don't have permission to manage employee permissions.
                </div>
            @else
                <div class="space-y-5">
                    {{-- Role Selector --}}
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm mb-3">Assigned Role</h3>
                        <div class="flex flex-wrap gap-2">
                            <button wire:click="$set('selectedRole', '')"
                                class="px-3 py-1.5 rounded-lg text-sm border-2 transition-colors
                                    {{ !$selectedRole ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600' }}">
                                No role
                            </button>
                            @foreach ($this->allRoles as $role)
                                <button wire:click="$set('selectedRole', '{{ $role->name }}')"
                                    class="px-3 py-1.5 rounded-lg text-sm border-2 transition-colors
                                        {{ $selectedRole === $role->name ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600' }}">
                                    {{ $role->name }}
                                    @if ($role->is_system)
                                        <span class="text-xs opacity-50">(system)</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        @if ($selectedRole)
                            <p class="text-xs text-gray-400 mt-1">
                                The role's built-in permissions are shown below. Additional direct permissions can be added.
                            </p>
                        @endif
                    </div>

                    {{-- Permission Groups --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-900 text-sm">Direct Permissions
                            <span class="text-xs font-normal text-gray-400 ml-1">
                                — checked permissions are granted IN ADDITION to the role above
                            </span>
                        </h3>

                        @foreach ($this->permissionsByGroup as $group => $permissions)
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-200">
                                    <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        {{ $group }}
                                    </h4>
                                </div>
                                <div class="p-4 grid sm:grid-cols-2 gap-2">
                                    @foreach ($permissions as $perm)
                                        <label class="flex items-start gap-2.5 cursor-pointer group">
                                            <div class="relative shrink-0 mt-0.5">
                                                <input type="checkbox" value="{{ $perm['name'] }}"
                                                    wire:model.live="selectedPermissions"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    {{ $perm['fromRole'] ? 'disabled' : '' }}>
                                                @if ($perm['fromRole'])
                                                    {{-- Visual overlay to show "from role" --}}
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-700 group-hover:text-gray-900">
                                                    {{ $perm['label'] }}
                                                </span>
                                                @if ($perm['fromRole'])
                                                    <span class="block text-xs text-indigo-400">
                                                        ✓ Included in {{ $selectedRole }} role
                                                    </span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Save --}}
                    @if ($permissionsChanged)
                        <div class="sticky bottom-4 z-10">
                            <div class="bg-white border border-indigo-200 rounded-xl p-4 shadow-lg flex items-center gap-4">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">Unsaved permission changes</div>
                                    <div class="text-xs text-gray-500">These changes will take effect on the employee's next
                                        request.</div>
                                </div>
                                <button wire:click="savePermissions" class="btn-primary">
                                    Save Permissions
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endcannot
        </div>
    </div>
</div>
