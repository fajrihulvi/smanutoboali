<?php

namespace App\Modules\GuestBook\Database\Factories;

use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Enums\GuestVisitSource;
use App\Modules\GuestBook\Models\GuestVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestVisit>
 */
class GuestVisitFactory extends Factory
{
    protected $model = GuestVisit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'institution' => fake()->company(),
            'whatsapp' => '08'.fake()->numerify('##########'),
            'category' => fake()->randomElement(GuestCategory::cases()),
            'purpose' => fake()->sentence(),
            'host' => fake()->randomElement(GuestHost::cases()),
            'notes' => null,
            'visited_at' => now(),
            'source' => GuestVisitSource::Website,
        ];
    }
}
