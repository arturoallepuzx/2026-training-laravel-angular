<?php

namespace Database\Factories;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = EloquentProduct::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'image_src' => null,
            'name' => fake()->words(2, true),
            'price' => fake()->numberBetween(100, 2500),
            'stock' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }
}
