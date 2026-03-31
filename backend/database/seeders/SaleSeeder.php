<?php

namespace Database\Seeders;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $invoicedOrders = EloquentOrder::where('restaurant_id', $restaurant->id)
            ->where('status', 'invoiced')
            ->get();

        foreach ($invoicedOrders as $order) {
            $orderLines = EloquentOrderLine::where('order_id', $order->id)->get();
            $totalSale = 0;

            foreach ($orderLines as $line) {
                $lineBaseTotal = $line->price * $line->quantity;
                $lineTaxTotal = intval(($lineBaseTotal * $line->tax_percentage) / 100);
                $totalSale += ($lineBaseTotal + $lineTaxTotal);
            }

            EloquentSale::factory()->create([
                'restaurant_id' => $restaurant->id,
                'order_id' => $order->id,
                'user_id' => $order->closed_by_user_id,
                'total' => $totalSale,
            ]);
        }
    }
}
