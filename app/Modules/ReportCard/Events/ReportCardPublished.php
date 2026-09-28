<?php

namespace App\Modules\ReportCard\Events;

use App\Modules\ReportCard\Models\ReportCard;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCardPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReportCard $card) {}
}
