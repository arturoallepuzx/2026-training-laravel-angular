<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        EloquentRestaurant::query()->firstOrCreate(
            ['uuid' => (string) config('superadmin.restaurant_uuid')],
            [
                'name' => 'Superadmin',
                'legal_name' => 'Superadmin',
                'tax_id' => 'SUPERADMIN',
                'email' => 'system@yurest.local',
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
