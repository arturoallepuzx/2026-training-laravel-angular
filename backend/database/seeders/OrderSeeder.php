<?php

namespace Database\Seeders;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $tables = EloquentTable::where('restaurant_id', $restaurant->id)->get();
        $operators = EloquentUser::where('restaurant_id', $restaurant->id)->where('role', 'operator')->get();

        if ($tables->isEmpty() || $operators->isEmpty()) {
            return;
        }

        foreach ($tables->random(3) as $table) {
            EloquentOrder::factory()->create([
                'restaurant_id' => $restaurant->id,
                'table_id' => $table->id,
                'status' => 'open',
                'opened_by_user_id' => $operators->random()->id,
                'closed_by_user_id' => null,
            ]);
        }

        foreach ($tables->random(10) as $table) {
            $openedById = $operators->random()->id;
            $closedById = $operators->random()->id;
            
            EloquentOrder::factory()->create([
                'restaurant_id' => $restaurant->id,
                'table_id' => $table->id,
                'status' => 'invoiced',
                'opened_by_user_id' => $openedById,
                'closed_by_user_id' => $closedById,
                'closed_at' => now()->subMinutes(rand(10, 300)),
            ]);
        }
    }
}
