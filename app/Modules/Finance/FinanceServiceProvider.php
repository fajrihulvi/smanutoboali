<?php

namespace App\Modules\Finance;

use App\Modules\Finance\Console\SendInvoiceReminders;
use App\Modules\Finance\Gateway\MidtransGateway;
use App\Modules\Finance\Gateway\PaymentGateway;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Policies\InvoicePolicy;
use App\Modules\Finance\Policies\PaymentPolicy;
use App\Providers\ModuleServiceProvider;

class FinanceServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PaymentGateway::class, MidtransGateway::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendInvoiceReminders::class]);
        }
    }
}
