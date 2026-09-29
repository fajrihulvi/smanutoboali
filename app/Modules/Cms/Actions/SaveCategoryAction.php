<?php

namespace App\Modules\Cms\Actions;

use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Services\SlugGenerator;

class SaveCategoryAction
{
    public function __construct(private SlugGenerator $slugs) {}

    public function handle(string $name, ?WebsiteCategory $category = null): WebsiteCategory
    {
        $category ??= new WebsiteCategory;
        $category->name = $name;
        $category->slug = $this->slugs->unique(WebsiteCategory::class, $name, $category->exists ? $category->id : null, 80);
        $category->save();

        return $category;
    }
}
