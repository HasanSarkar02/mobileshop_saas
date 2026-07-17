<?php

namespace App\Livewire\Reports;

use App\Reporting\Repositories\CustomerRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Customer Due Report')]
class CustomerDueReport extends Component
{
    use \App\Traits\HasAuthorization;
    
    #[Url(as: 'view')]
    public string $activeView = 'customers'; // customers | finance_partners

    public function mount(): void
{
    $this->requirePermission('reports.view');
}

    #[Computed]
    public function customerDues(): Collection
    {
        return app(CustomerRepository::class)->dueAging(Auth::user()->shop_id);
    }

    #[Computed]
    public function fpReceivables(): Collection
    {
        return app(CustomerRepository::class)->fpReceivablesAging(Auth::user()->shop_id);
    }

    #[Computed]
    public function customerStats(): object
    {
        return app(CustomerRepository::class)->stats(Auth::user()->shop_id);
    }

    public function render()
    {
        return view('livewire.reports.customer-due-report');
    }
}