<?php

namespace App\Modules\Cms\Models;

use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $slug
 */
class WebsiteCategory extends Model
{
    use HasPublicId;

    protected $fillable = ['name', 'slug'];

    protected $hidden = ['id'];

    /**
     * @return HasMany<WebsitePost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(WebsitePost::class, 'category_id');
    }
}
