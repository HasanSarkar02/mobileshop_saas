<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header Card --}}
    <div class="card p-6 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $shop->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $shop->email }} · {{ $shop->slug }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('partials.shop-status-badge', ['status' => $shop->status])
            @if ($shop->is_active)
                <button wire:click="suspend" wire:confirm="Suspend this shop? All users will be locked out."
                    class="btn btn-sm btn-danger">Suspend</button>
            @else
                <button wire:click="activate" class="btn btn-sm btn-success">Activate</button>
            @endif
            <button wire:click="impersonate" class="btn btn-sm btn-secondary">
                Login as Owner
            </button>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-6">
        {{-- Shop Info --}}
        <div class="card p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Shop Details</h3>
            @foreach ([['label' => 'Business Type', 'value' => ucfirst(str_replace('_', ' ', $shop->business_type))], ['label' => 'Phone', 'value' => $shop->phone ?? '—'], ['label' => 'Address', 'value' => $shop->address ?? '—'], ['label' => 'Trial Ends', 'value' => $shop->trial_ends_at?->format('d M Y') ?? '—'], ['label' => 'VAT', 'value' => $shop->vat_enabled ? $shop->vat_registration_number . ' (' . $shop->default_vat_rate . '%)' : 'Disabled']] as $row)
                <div class="flex gap-3">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                    <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Owner Info --}}
        <div class="card p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Owner</h3>
            @if ($shop->owner)
                @foreach ([['label' => 'Name', 'value' => $shop->owner->name], ['label' => 'Email', 'value' => $shop->owner->email], ['label' => 'Phone', 'value' => $shop->owner->phone ?? '—'], ['label' => 'Last Login', 'value' => $shop->owner->last_login_at?->format('d M Y H:i') ?? 'Never'], ['label' => 'Password Set', 'value' => $shop->owner->password_changed_at ? $shop->owner->password_changed_at->format('d M Y') : 'Not yet']] as $row)
                    <div class="flex gap-3">
                        <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                        <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-400">No owner yet.</p>
            @endif
        </div>
    </div>

    {{-- Branches --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Branches ({{ $shop->branches->count() }})</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Name</th>
                    <th class="table-th">Code</th>
                    <th class="table-th">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($shop->branches as $branch)
                    <tr>
                        <td class="table-td font-medium">{{ $branch->name }}@if ($branch->is_main)
                                <span class="badge badge-blue ml-1">Main</span>
                            @endif
                        </td>
                        <td class="table-td text-gray-500">{{ $branch->code }}</td>
                        <td class="table-td"><span
                                class="{{ $branch->is_active ? 'badge-green' : 'badge-gray' }} badge">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
