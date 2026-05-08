<?php

namespace Database\Factories;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentRestaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = EloquentRestaurant::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->company(),
            'legal_name' => fake()->company().' S.L.',
            'tax_id' => 'B'.fake()->randomNumber(8, true),
            'email' => fake()->unique()->companyEmail(),
            'password' => null,
        ];
    }
}
