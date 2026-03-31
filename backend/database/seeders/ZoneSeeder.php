<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $zoneNames = ['Terraza', 'Salón Principal', 'Barra'];
        
        foreach ($zoneNames as $zoneName) {
            EloquentZone::factory()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $zoneName,
            ]);
        }
    }
}
