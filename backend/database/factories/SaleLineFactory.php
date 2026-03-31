<?php

namespace Database\Factories;

use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleLineFactory extends Factory
{
    protected $model = EloquentSaleLine::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'quantity' => fake()->numberBetween(1, 3),
            'price' => fake()->numberBetween(100, 2500),
            'tax_percentage' => 21,
        ];
    }
}
