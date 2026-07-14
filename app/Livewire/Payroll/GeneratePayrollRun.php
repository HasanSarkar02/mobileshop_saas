<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\GeneratePayrollRunAction;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeSalaryStructure;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Generate Payroll')]
class GeneratePayrollRun extends Component
{
    use \App\Traits\HasAuthorization;

    // ── Step tracking ──────────────────────────────────────────────────────────
    public int    $step        = 1; // 1=scope, 2=preview, 3=confirm

    // ── Step 1: Scope ──────────────────────────────────────────────────────────
    public int    $year        = 0;
    public int    $month       = 0;
    public int    $branchId    = 0;
    public int    $departmentId= 0;
    public string $employmentType = '';
    public string $description = '';

    // ── Step 2: Preview ───────────────────────────────────────────────────────
    public array  $previewEmployees = [];
    public array  $warnings         = [];

    // ── Generation result ──────────────────────────────────────────────────────
    public ?int   $generatedRunId   = null;

    public function mount(): void
    {
        $this->requirePermission('payroll.generate');
        $this->year  = (int) now()->format('Y');
        $this->month = (int) now()->format('m');
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    #[Computed]
    public function departments(): \Illuminate\Database\Eloquent\Collection
    {
        return Department::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    #[Computed]
    public function monthOptions(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn ($m) => [
            $m => \Carbon\Carbon::createFromDate(null, $m, 1)->format('F'),
        ])->toArray();
    }

    #[Computed]
    public function yearOptions(): array
    {
        $y = now()->year;
        return [$y - 1 => $y - 1, $y => $y, $y + 1 => $y + 1];
    }

    public function goToPreview(): void
    {
        $this->validate([
            'year'  => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $periodTo  = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)
            ->endOfMonth()->toDateString();

        // Resolve employees eligible for this run
        $query = \App\Models\User::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->where('user_type', 'employee')
            ->whereHas('employeeSalaryStructures', function ($q) use ($periodTo) {
                // ← relationship now exists on User model
                $q->where('is_active', true)
                  ->where('effective_from', '<=', $periodTo)
                  ->where(fn ($sq) =>
                      $sq->whereNull('effective_to')
                         ->orWhere('effective_to', '>=', $periodTo)
                  );

                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->employmentType) {
                    $q->where('employment_type', $this->employmentType);
                }
            })
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));

        $employees = $query
            ->with([
                'employeeSalaryStructures' => fn ($q) =>
                    $q->where('is_active', true)->with(['policy', 'department']),
            ])
            ->get();

        $this->warnings         = [];
        $this->previewEmployees = [];

        foreach ($employees as $emp) {
            $structure = $emp->employeeSalaryStructures->first();
            $warnFlags = [];

            if (in_array($emp->employment_status ?? 'active', ['resigned', 'terminated'])) {
                $warnFlags[] = '⚠ ' . ucfirst($emp->employment_status);
            }

            $this->previewEmployees[] = [
                'id'              => $emp->id,
                'name'            => $emp->name,
                'designation'     => $structure?->designation ?? '—',
                'department'      => $structure?->department?->name ?? '—',
                'policy'          => $structure?->policy?->name ?? '—',
                'employment_type' => $structure?->employment_type?->label() ?? '—',
                'working_days'    => $structure?->monthly_working_days ?? 26,
                'warnings'        => $warnFlags,
            ];
        }

        if (empty($this->previewEmployees)) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'No employees found with active salary structures for the selected scope.']);
            return;
        }

        // Check for existing run
        $existing = \App\Models\PayrollRun::where('shop_id', Auth::user()->shop_id)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->when($this->branchId,      fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId,  fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->employmentType,fn ($q) => $q->where('employment_type', $this->employmentType))
            ->whereNotIn('status', ['cancelled', 'reversed'])
            ->first();

        if ($existing) {
            $this->warnings[] = "⚠ A payroll run ({$existing->run_number}) already exists for this scope. " .
                                "Cancel it first to regenerate.";
        }

        $this->step = 2;
    }

    public function generate(GeneratePayrollRunAction $action): void
    {
        $this->requirePermission('payroll.generate');

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $result = $action->execute($shop, [
                'year'            => $this->year,
                'month'           => $this->month,
                'branch_id'       => $this->branchId ?: null,
                'department_id'   => $this->departmentId ?: null,
                'employment_type' => $this->employmentType ?: null,
                'description'     => $this->description ?: null,
            ], Auth::user());

            $run              = $result['run'];
            $this->warnings   = array_merge($this->warnings, $result['warnings']);
            $this->generatedRunId = $run->id;
            $this->step       = 3;

            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$run->run_number} generated with {$run->total_employees} employee(s)."]);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.generate-payroll-run');
    }
}