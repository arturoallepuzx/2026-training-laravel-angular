<?php

namespace Database\Factories;

use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderLineFactory extends Factory
{
    protected $model = EloquentOrderLine::class;

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
