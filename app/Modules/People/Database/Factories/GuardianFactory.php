<?php

namespace App\Modules\People\Database\Factories;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nik' => fake()->numerify('19##############'),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'occupation' => fake()->randomElement(['Wiraswasta', 'PNS', 'Karyawan Swasta', 'Petani', 'Nelayan', 'Ibu Rumah Tangga']),
            'address' => fake()->address(),
        ];
    }

    /**
     * Orang tua dengan akun login ber-role guardian.
     */
    public function withAccount(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->getKey() ?? User::factory()->withRole(Role::Guardian)->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ])->getKey(),
        ]);
    }
}
