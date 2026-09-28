<?php

namespace App\Modules\Portfolio\Events;

use App\Modules\Portfolio\Models\Portfolio;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PortfolioPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Portfolio $portfolio) {}
}
