<?php

namespace Database\Seeders;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public const SUPERADMIN_USER_EMAIL = 'superadmin@yurest.local';

    public function run(): void
    {
        $superadminRestaurant = EloquentRestaurant::query()
            ->where('email', (string) config('superadmin.restaurant_email'))
            ->firstOrFail();

        EloquentUser::query()->firstOrCreate(
            ['email' => self::SUPERADMIN_USER_EMAIL],
            [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $superadminRestaurant->id,
                'role' => 'superadmin',
                'name' => 'Superadmin',
                'password' => Hash::make('superadmin'),
                'pin' => null,
                'image_src' => null,
            ]
        );

        $tenantRestaurant = EloquentRestaurant::query()
            ->where('email', 'admin@tpv.test')
            ->firstOrFail();

        EloquentUser::factory()->create([
            'restaurant_id' => $tenantRestaurant->id,
            'name' => 'Admin User',
            'email' => 'admin@tpv.test',
            'role' => 'admin',
            'pin' => '1111',
        ]);

        EloquentUser::factory()->create([
            'restaurant_id' => $tenantRestaurant->id,
            'name' => 'Supervisor User',
            'email' => 'supervisor@tpv.test',
            'role' => 'supervisor',
            'pin' => '2222',
        ]);

        EloquentUser::factory(3)->create([
            'restaurant_id' => $tenantRestaurant->id,
            'role' => 'operator',
        ]);
    }
}
