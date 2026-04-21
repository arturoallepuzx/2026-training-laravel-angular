<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();

        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA General (21%)',
            'percentage' => 21,
        ]);

        EloquentTax::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'IVA Reducido (10%)',
            'percentage' => 10,
        ]);
    }
}
