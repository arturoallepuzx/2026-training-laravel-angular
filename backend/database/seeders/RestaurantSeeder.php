<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        EloquentRestaurant::factory()->create([
            'name' => 'Restaurante TPV Demo',
            'legal_name' => 'Demo TPV S.L.',
            'tax_id' => 'B12345678',
            'email' => 'admin@tpv.test',
        ]);
    }
}
