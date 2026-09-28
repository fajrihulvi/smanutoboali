<?php

namespace App\Modules\Assessment\Events;

use App\Modules\Assessment\Models\GradeComponent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public GradeComponent $component) {}
}
