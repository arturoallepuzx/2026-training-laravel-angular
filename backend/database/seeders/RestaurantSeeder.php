<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        EloquentRestaurant::query()->firstOrCreate(
            ['email' => (string) config('superadmin.restaurant_email')],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Superadmin',
                'legal_name' => 'Superadmin',
                'tax_id' => 'SUPERADMIN',
                'password' => null,
            ]
        );

        EloquentRestaurant::factory()->create([
            'name' => 'Restaurante TPV Demo',
            'legal_name' => 'Demo TPV S.L.',
            'tax_id' => 'B12345678',
            'email' => 'admin@tpv.test',
        ]);
    }
}
