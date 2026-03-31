<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();

        EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Admin User',
            'email' => 'admin@tpv.test',
            'role' => 'admin',
            'pin' => '1111',
        ]);

        EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Supervisor User',
            'email' => 'supervisor@tpv.test',
            'role' => 'supervisor',
            'pin' => '2222',
        ]);

        EloquentUser::factory(3)->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
        ]);
    }
}
