<?php

namespace Database\Seeders;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $families = EloquentFamily::where('restaurant_id', $restaurant->id)->get();
        $taxGeneral = EloquentTax::where('restaurant_id', $restaurant->id)->where('percentage', 21)->first();
        $taxReduced = EloquentTax::where('restaurant_id', $restaurant->id)->where('percentage', 10)->first();

        foreach ($families as $family) {
            $taxId = ($family->name === 'Bebidas') ? $taxGeneral->id : $taxReduced->id;

            EloquentProduct::factory(5)->create([
                'restaurant_id' => $restaurant->id,
                'family_id' => $family->id,
                'tax_id' => $taxId,
            ]);
        }
    }
}
