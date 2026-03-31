<?php

namespace Database\Factories;

use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleFactory extends Factory
{
    protected $model = EloquentSale::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ticket_number' => fake()->unique()->numberBetween(1, 10000),
            'value_date' => now(),
            'total' => fake()->numberBetween(100, 10000),
        ];
    }
}
