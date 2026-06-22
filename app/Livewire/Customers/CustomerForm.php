<?php

namespace App\Livewire\Customers;

use App\Enums\CustomerIdType;
use App\Enums\CustomerTransactionType;
use App\Enums\CustomerType;
use App\Enums\GuarantorRelation;
use App\Models\Customer;
use App\Models\CustomerGuarantor;
use App\Services\CustomerLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Customer')]
class CustomerForm extends Component
{
    use WithFileUploads;

    public ?Customer $customer = null;

    // ── Basic Info ────────────────────────────────────────────────────────────
    public string $customerType  = 'regular';
    public string $name          = '';
    public string $phone         = '';
    public string $phoneAlt      = '';
    public string $email         = '';
    public string $address       = '';
    public string $district      = '';
    public string $thana         = '';
    public string $dateOfBirth   = '';
    public string $gender        = '';
    public string $occupation    = '';
    public string $notes         = '';

    // ── Financial ─────────────────────────────────────────────────────────────
    public string $creditLimit    = '0';
    public string $openingBalance = '0'; // only for new customers

    // ── Documents ─────────────────────────────────────────────────────────────
    public string $idType    = '';
    public string $idNumber  = '';
    public $photo;       // Livewire temp upload
    public $idFront;     // Livewire temp upload
    public $idBack;      // Livewire temp upload

    // ── Guarantor ─────────────────────────────────────────────────────────────
    public bool   $hasGuarantor      = false;
    public string $guarantorName     = '';
    public string $guarantorPhone    = '';
    public string $guarantorPhoneAlt = '';
    public string $guarantorAddress  = '';
    public string $guarantorRelation = 'other';
    public string $guarantorNid      = '';
    public $guarantorPhoto;    // Livewire temp upload
    public $guarantorNidFront; // Livewire temp upload
    public $guarantorNidBack;  // Livewire temp upload

    public function mount(?Customer $customer = null): void
    {
        if ($customer && $customer->exists) {
            $this->customer     = $customer->load('guarantor');
            $this->customerType = $customer->customer_type->value;
            $this->name         = $customer->name;
            $this->phone        = $customer->phone;
            $this->phoneAlt     = $customer->phone_alt ?? '';
            $this->email        = $customer->email ?? '';
            $this->address      = $customer->address ?? '';
            $this->district     = $customer->district ?? '';
            $this->thana        = $customer->thana ?? '';
            $this->dateOfBirth  = $customer->date_of_birth?->format('Y-m-d') ?? '';
            $this->gender       = $customer->gender ?? '';
            $this->occupation   = $customer->occupation ?? '';
            $this->notes        = $customer->notes ?? '';
            $this->creditLimit  = (string) $customer->credit_limit;
            $this->idType       = $customer->id_type ?? '';
            $this->idNumber     = $customer->id_number ?? '';

            if ($g = $customer->guarantor) {
                $this->hasGuarantor      = true;
                $this->guarantorName     = $g->name;
                $this->guarantorPhone    = $g->phone;
                $this->guarantorPhoneAlt = $g->phone_alt ?? '';
                $this->guarantorAddress  = $g->address ?? '';
                $this->guarantorRelation = $g->relation->value;
                $this->guarantorNid      = $g->nid_number ?? '';
            }
        }
    }

    public function updatedCustomerType(): void
    {
        // Auto-enable guarantor for credit customers
        if ($this->customerType === CustomerType::Credit->value) {
            $this->hasGuarantor = true;
        }
    }

    public function save(CustomerLedgerService $ledger): void
    {
        $isNew = ! $this->customer?->exists;

        $this->validate([
            'customerType'  => 'required|in:' . implode(',', array_column(CustomerType::cases(), 'value')),
            'name'          => 'required|string|max:255',
            'phone'         => ['required', 'string', 'max:20', $isNew
                ? \Illuminate\Validation\Rule::unique('customers')->where('shop_id', Auth::user()->shop_id)
                : \Illuminate\Validation\Rule::unique('customers')->where('shop_id', Auth::user()->shop_id)->ignore($this->customer?->id)
            ],
            'phoneAlt'      => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'creditLimit'   => 'nullable|numeric|min:0',
            'openingBalance'=> 'nullable|numeric|min:0',
            'photo'         => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
            'idFront'       => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
            'idBack'        => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
            'guarantorName'     => $this->hasGuarantor ? 'required|string|max:255' : 'nullable',
            'guarantorPhone'    => $this->hasGuarantor ? 'required|string|max:20' : 'nullable',
            'guarantorNidFront' => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
            'guarantorNidBack'  => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
            'guarantorPhoto'    => 'nullable|image|max:3072|mimes:jpg,jpeg,png,webp',
        ], [
            'phone.unique' => 'This phone number is already registered as a customer.',
        ]);

        DB::transaction(function () use ($ledger, $isNew) {
            $shopId = Auth::user()->shop_id;
            $dir    = "shops/{$shopId}/customers";

            $photoPath   = $this->photo    ? $this->photo->store("{$dir}", 'public')    : $this->customer?->photo_path;
            $idFrontPath = $this->idFront  ? $this->idFront->store("{$dir}", 'public')  : $this->customer?->id_front_path;
            $idBackPath  = $this->idBack   ? $this->idBack->store("{$dir}", 'public')   : $this->customer?->id_back_path;

            $data = [
                'shop_id'       => $shopId,
                'customer_type' => $this->customerType,
                'name'          => $this->name,
                'phone'         => $this->phone,
                'phone_alt'     => $this->phoneAlt ?: null,
                'email'         => $this->email ?: null,
                'address'       => $this->address ?: null,
                'district'      => $this->district ?: null,
                'thana'         => $this->thana ?: null,
                'date_of_birth' => $this->dateOfBirth ?: null,
                'gender'        => $this->gender ?: null,
                'occupation'    => $this->occupation ?: null,
                'notes'         => $this->notes ?: null,
                'credit_limit'  => (float) $this->creditLimit,
                'id_type'       => $this->idType ?: null,
                'id_number'     => $this->idNumber ?: null,
                'photo_path'    => $photoPath,
                'id_front_path' => $idFrontPath,
                'id_back_path'  => $idBackPath,
                'created_by'    => Auth::id(),
            ];

            if ($isNew) {
                $customer = Customer::create(['is_active' => true, ...$data]);
            } else {
                $this->customer->update($data);
                $customer = $this->customer->fresh();
            }

            // Opening balance (new customers only)
            if ($isNew && (float) $this->openingBalance > 0) {
                $ledger->recordOpeningBalance($customer, (float) $this->openingBalance, Auth::user());
            }

            // Guarantor
            if ($this->hasGuarantor) {
                $guarantorDir = "{$dir}";
                $gPhoto    = $this->guarantorPhoto    ? $this->guarantorPhoto->store($guarantorDir, 'public')    : $customer->guarantor?->photo_path;
                $gNidFront = $this->guarantorNidFront ? $this->guarantorNidFront->store($guarantorDir, 'public') : $customer->guarantor?->nid_front_path;
                $gNidBack  = $this->guarantorNidBack  ? $this->guarantorNidBack->store($guarantorDir, 'public')  : $customer->guarantor?->nid_back_path;

                CustomerGuarantor::updateOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'shop_id'       => $shopId,
                        'name'          => $this->guarantorName,
                        'phone'         => $this->guarantorPhone,
                        'phone_alt'     => $this->guarantorPhoneAlt ?: null,
                        'address'       => $this->guarantorAddress ?: null,
                        'relation'      => $this->guarantorRelation,
                        'nid_number'    => $this->guarantorNid ?: null,
                        'photo_path'    => $gPhoto,
                        'nid_front_path' => $gNidFront,
                        'nid_back_path'  => $gNidBack,
                    ]
                );
            }
        });

        $this->dispatch('notify', type: 'success',
            message: $isNew ? "Customer \"{$this->name}\" created." : "Customer \"{$this->name}\" updated.");
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customers.customer-form');
    }
}