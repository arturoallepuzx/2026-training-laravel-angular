<?php

namespace Database\Seeders;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Seeder;

class OrderLineSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $orders = EloquentOrder::where('restaurant_id', $restaurant->id)->get();
        $products = EloquentProduct::where('restaurant_id', $restaurant->id)->get();

        if ($orders->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            $orderProducts = $products->random(rand(2, 5));
            
            foreach ($orderProducts as $product) {
                $qty = rand(1, 4);
                $taxPercentage = EloquentTax::find($product->tax_id)->percentage;

                EloquentOrderLine::factory()->create([
                    'restaurant_id' => $restaurant->id,
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'user_id' => $order->opened_by_user_id,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'tax_percentage' => $taxPercentage,
                ]);
            }
        }
    }
}
