<?php

namespace Database\Factories;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentUser>
 */
class UserFactory extends Factory
{
    protected $model = EloquentUser::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => null,
            'role' => 'operator',
            'image_src' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'pin' => fake()->numerify('####'),
        ];
    }
}