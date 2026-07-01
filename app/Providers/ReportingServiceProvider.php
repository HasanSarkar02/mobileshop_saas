<?php

namespace App\Providers;

use App\Reporting\Repositories\CustomerRepository;
use App\Reporting\Repositories\ExpenseRepository;
use App\Reporting\Repositories\FinancialRepository;
use App\Reporting\Repositories\InventoryRepository;
use App\Reporting\Repositories\PayrollRepository;
use App\Reporting\Repositories\SalesRepository;
use App\Reporting\Repositories\ServiceRepository;
use App\Reporting\Services\ExecutiveDashboardService;
use App\Reporting\Services\FinancialReportService;
use App\Reporting\Services\SalesReportService;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories — new instance per resolution (queries are stateless)
        $this->app->bind(SalesRepository::class);
        $this->app->bind(InventoryRepository::class);
        $this->app->bind(FinancialRepository::class);
        $this->app->bind(CustomerRepository::class);
        $this->app->bind(ServiceRepository::class);
        $this->app->bind(ExpenseRepository::class);
        $this->app->bind(PayrollRepository::class);

        // Services — also new instances (inject repos)
        $this->app->bind(ExecutiveDashboardService::class, fn ($app) =>
            new ExecutiveDashboardService(
                $app->make(SalesRepository::class),
                $app->make(InventoryRepository::class),
                $app->make(FinancialRepository::class),
                $app->make(CustomerRepository::class),
                $app->make(ServiceRepository::class),
                $app->make(ExpenseRepository::class),
                $app->make(PayrollRepository::class),
            )
        );

        $this->app->bind(SalesReportService::class, fn ($app) =>
            new SalesReportService($app->make(SalesRepository::class))
        );

        $this->app->bind(FinancialReportService::class, fn ($app) =>
            new FinancialReportService($app->make(FinancialRepository::class))
        );
    }
}