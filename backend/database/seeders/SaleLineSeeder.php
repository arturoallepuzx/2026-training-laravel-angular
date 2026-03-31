<?php

namespace Database\Seeders;

use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use Illuminate\Database\Seeder;

class SaleLineSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $sales = EloquentSale::where('restaurant_id', $restaurant->id)->get();

        foreach ($sales as $sale) {
            $orderLines = EloquentOrderLine::where('order_id', $sale->order_id)->get();

            foreach ($orderLines as $line) {
                EloquentSaleLine::factory()->create([
                    'restaurant_id' => $restaurant->id,
                    'sale_id' => $sale->id,
                    'order_line_id' => $line->id,
                    'user_id' => $line->user_id,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'tax_percentage' => $line->tax_percentage,
                ]);
            }
        }
    }
}
