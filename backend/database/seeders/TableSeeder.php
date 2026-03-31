<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $zones = EloquentZone::where('restaurant_id', $restaurant->id)->get();

        foreach ($zones as $zone) {
            EloquentTable::factory(5)->create([
                'restaurant_id' => $restaurant->id,
                'zone_id' => $zone->id,
            ]);
        }
    }
}
