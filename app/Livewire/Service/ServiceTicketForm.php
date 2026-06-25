<?php
namespace App\Livewire\Service;

use App\Enums\ServiceTicketStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductUnit;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketPart;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Service Ticket')]
class ServiceTicketForm extends Component
{
    use \App\Traits\HasAuthorization;

    public ?ServiceTicket $ticket = null;

    // Customer
    public string $customerName  = '';
    public string $customerPhone = '';
    public int    $customerId    = 0;
    public string $customerSearch = '';
    public array  $customerResults = [];
    public bool   $showCustDrop  = false;

    // Device
    public string $deviceBrand     = '';
    public string $deviceModel     = '';
    public string $deviceImei      = '';
    public string $deviceColor     = '';
    public string $deviceCondition = '';

    // Service
    public string $problemDescription = '';
    public string $diagnosisNotes     = '';
    public string $internalNotes      = '';
    public string $estimatedCost      = '';
    public string $laborCharge        = '';
    public int    $technicianId       = 0;
    public int    $branchId           = 0;
    public bool   $isWarrantyService  = false;
    public int    $linkedUnitId       = 0;

    // Parts
    public array  $parts              = [];
    public string $partSearch         = '';
    public array  $partResults        = [];
    public bool   $showPartDrop       = false;

    public function mount(?ServiceTicket $ticket = null): void
    {
        $this->requirePermission('service.manage');

        $this->branchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );

        if ($ticket && $ticket->exists) {
            $this->ticket = $ticket->load('parts');
            $this->fill([
                'customerName'       => $ticket->customer_name,
                'customerPhone'      => $ticket->customer_phone ?? '',
                'customerId'         => $ticket->customer_id ?? 0,
                'deviceBrand'        => $ticket->device_brand ?? '',
                'deviceModel'        => $ticket->device_model ?? '',
                'deviceImei'         => $ticket->device_imei ?? '',
                'deviceColor'        => $ticket->device_color ?? '',
                'deviceCondition'    => $ticket->device_condition ?? '',
                'problemDescription' => $ticket->problem_description,
                'diagnosisNotes'     => $ticket->diagnosis_notes ?? '',
                'internalNotes'      => $ticket->internal_notes ?? '',
                'estimatedCost'      => (string) $ticket->estimated_cost,
                'laborCharge'        => (string) $ticket->labor_charge,
                'technicianId'       => $ticket->technician_id ?? 0,
                'branchId'           => $ticket->branch_id,
                'isWarrantyService'  => $ticket->is_warranty_service,
                'linkedUnitId'       => $ticket->product_unit_id ?? 0,
            ]);

            $this->parts = $ticket->parts->map(fn ($p) => [
                'id'               => $p->id,
                'part_description' => $p->part_description,
                'product_variant_id' => $p->product_variant_id,
                'quantity'         => $p->quantity,
                'unit_cost'        => $p->unit_cost,
                'line_total'       => $p->line_total,
                'from_inventory'   => $p->from_inventory,
                '_saved'           => true,
            ])->toArray();
        }
    }

    public function updatedCustomerSearch(): void
    {
        if (strlen(trim($this->customerSearch)) < 2) {
            $this->customerResults = [];
            $this->showCustDrop = false;
            return;
        }
        $this->customerResults = Customer::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where(fn ($q) =>
                $q->where('name', 'like', "%{$this->customerSearch}%")
                  ->orWhere('phone', 'like', "%{$this->customerSearch}%")
            )
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
        $this->showCustDrop = ! empty($this->customerResults);
    }

    public function selectCustomer(int $id, string $name, string $phone): void
    {
        $this->customerId     = $id;
        $this->customerName   = $name;
        $this->customerPhone  = $phone;
        $this->customerSearch = '';
        $this->showCustDrop   = false;
    }

    public function updatedPartSearch(): void
    {
        if (strlen(trim($this->partSearch)) < 2) {
            $this->partResults = [];
            $this->showPartDrop = false;
            return;
        }
        $shopId = Auth::user()->shop_id;
        $this->partResults = ProductVariant::with('product.brand')
            ->whereHas('product', fn ($q) =>
                $q->where('shop_id', $shopId)->where('is_active', true)
                  ->where('tracking_type', 'non_serialized')
                  ->where('name', 'like', "%{$this->partSearch}%")
            )
            ->where('is_active', true)
            ->limit(6)
            ->get()
            ->map(fn ($v) => [
                'id'    => $v->id,
                'label' => ($v->product->name . ($v->attributes_label ? ' — '.$v->attributes_label : '')),
                'sku'   => $v->sku,
            ])
            ->toArray();
        $this->showPartDrop = ! empty($this->partResults);
    }

    public function addPartFromInventory(int $variantId, string $label): void
    {
        $this->parts[] = [
            'id'               => null,
            'part_description' => $label,
            'product_variant_id' => $variantId,
            'quantity'         => 1,
            'unit_cost'        => '',
            'line_total'       => 0,
            'from_inventory'   => true,
            '_saved'           => false,
        ];
        $this->partSearch   = '';
        $this->partResults  = [];
        $this->showPartDrop = false;
    }

    public function addExternalPart(): void
    {
        $this->parts[] = [
            'id'               => null,
            'part_description' => '',
            'product_variant_id' => null,
            'quantity'         => 1,
            'unit_cost'        => '',
            'line_total'       => 0,
            'from_inventory'   => false,
            '_saved'           => false,
        ];
    }

    public function removePart(int $idx): void
    {
        array_splice($this->parts, $idx, 1);
    }

    public function updatedParts(mixed $value, string $key): void
    {
        [$idx, $field] = array_pad(explode('.', $key, 2), 2, null);
        $idx = (int) $idx;
        if (in_array($field, ['quantity', 'unit_cost'])) {
            $qty  = (float) ($this->parts[$idx]['quantity'] ?? 1);
            $cost = (float) ($this->parts[$idx]['unit_cost'] ?? 0);
            $this->parts[$idx]['line_total'] = round($qty * $cost, 2);
        }
    }

    #[Computed]
    public function technicians(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\User::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get();
    }

    #[Computed]
    public function totalPartsEstimate(): float
    {
        return collect($this->parts)->sum(fn ($p) => (float) ($p['line_total'] ?? 0));
    }

    #[Computed]
    public function totalEstimate(): float
    {
        return $this->totalPartsEstimate + (float) ($this->laborCharge ?? 0);
    }

    public function save(): void
    {
        $this->validate([
            'customerName'       => 'required|string|max:255',
            'problemDescription' => 'required|string|min:5',
            'branchId'           => 'required|integer|min:1',
            'laborCharge'        => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () {
            $shopId = Auth::user()->shop_id;
            $isNew  = ! $this->ticket?->exists;

            $ticketData = [
                'shop_id'            => $shopId,
                'branch_id'          => $this->branchId,
                'customer_id'        => $this->customerId ?: null,
                'customer_name'      => $this->customerName,
                'customer_phone'     => $this->customerPhone ?: null,
                'device_brand'       => $this->deviceBrand ?: null,
                'device_model'       => $this->deviceModel ?: null,
                'device_imei'        => $this->deviceImei ?: null,
                'device_color'       => $this->deviceColor ?: null,
                'device_condition'   => $this->deviceCondition ?: null,
                'problem_description'=> $this->problemDescription,
                'diagnosis_notes'    => $this->diagnosisNotes ?: null,
                'internal_notes'     => $this->internalNotes ?: null,
                'estimated_cost'     => (float) ($this->estimatedCost ?: 0),
                'labor_charge'       => (float) ($this->laborCharge ?: 0),
                'is_warranty_service'=> $this->isWarrantyService,
                'technician_id'      => $this->technicianId ?: null,
                'product_unit_id'    => $this->linkedUnitId ?: null,
            ];

            if ($isNew) {
                $ticketData['ticket_number'] = $this->nextTicketNumber($shopId);
                $ticketData['status']        = ServiceTicketStatus::Received->value;
                $ticketData['received_at']   = now();
                $ticketData['created_by']    = Auth::id();
                $ticket = ServiceTicket::create($ticketData);
            } else {
                $this->ticket->update($ticketData);
                $ticket = $this->ticket->fresh();
            }

            // Save parts
            foreach ($this->parts as $p) {
                if (! empty($p['id'])) {
                    ServiceTicketPart::find($p['id'])?->update([
                        'part_description'   => $p['part_description'],
                        'quantity'           => (int) $p['quantity'],
                        'unit_cost'          => (float) $p['unit_cost'],
                        'line_total'         => (float) $p['line_total'],
                    ]);
                } else {
                    ServiceTicketPart::create([
                        'ticket_id'          => $ticket->id,
                        'product_variant_id' => $p['product_variant_id'] ?? null,
                        'part_description'   => $p['part_description'],
                        'quantity'           => (int) $p['quantity'],
                        'unit_cost'          => (float) $p['unit_cost'],
                        'line_total'         => (float) $p['line_total'],
                        'from_inventory'     => (bool) $p['from_inventory'],
                    ]);
                }
            }

            $ticket->recalculateTotals();

            $this->dispatch('notify', ['type' => 'success',
                'message' => $isNew ? "Ticket {$ticket->ticket_number} created." : "Ticket updated."]);
            $this->redirect(route('service.show', $ticket), navigate: true);
        });
    }

    private function nextTicketNumber(int $shopId): string
    {
        $year = now()->format('Y');
        DB::statement(
            'INSERT INTO shop_counters (shop_id, counter_key, current_value, created_at, updated_at)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE current_value = current_value + 1, updated_at = NOW()',
            [$shopId, "service_{$year}"]
        );
        $seq = DB::table('shop_counters')
            ->where('shop_id', $shopId)
            ->where('counter_key', "service_{$year}")
            ->value('current_value');
        return sprintf('SVC-%s-%05d', $year, $seq);
    }

    public function render()
    {
        return view('livewire.service.service-ticket-form');
    }
}