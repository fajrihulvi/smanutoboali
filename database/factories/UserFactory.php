<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Beri satu atau beberapa role inti (role dibuat bila belum ada).
     */
    public function withRole(Role ...$roles): static
    {
        return $this->afterCreating(function (User $user) use ($roles) {
            foreach ($roles as $role) {
                RoleModel::findOrCreate($role->value, 'web');
                $user->assignRole($role->value);
            }
        });
    }
}
