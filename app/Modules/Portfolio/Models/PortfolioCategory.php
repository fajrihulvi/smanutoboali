<?php

namespace App\Modules\Portfolio\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $position
 */
class PortfolioCategory extends Model
{
    protected $fillable = ['code', 'name', 'position'];
}
