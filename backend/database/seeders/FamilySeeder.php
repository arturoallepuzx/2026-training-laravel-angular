<?php

namespace Database\Seeders;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();

        $familyNames = ['Bebidas', 'Entrantes', 'Platos Principales', 'Postres'];

        foreach ($familyNames as $familyName) {
            EloquentFamily::factory()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $familyName,
            ]);
        }
    }
}
