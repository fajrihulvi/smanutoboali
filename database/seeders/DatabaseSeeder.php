<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeders\DemoUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data demo lokal (php artisan migrate:fresh --seed).
     */
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
        ]);
    }
}
