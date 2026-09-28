<?php

namespace App\Modules\Finance\Console;

use App\Modules\Finance\Actions\SendInvoiceRemindersAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'finance:remind';

    protected $description = 'Kirim pengingat tagihan H-3 dan H+1 jatuh tempo';

    public function handle(SendInvoiceRemindersAction $action): int
    {
        $this->info($action->handle(CarbonImmutable::today()).' pengingat dikirim.');

        return self::SUCCESS;
    }
}
