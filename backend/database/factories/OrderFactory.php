<?php

namespace Database\Factories;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = EloquentOrder::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'status' => 'open',
            'diners' => fake()->numberBetween(1, 6),
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }
}
