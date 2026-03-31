<?php

namespace Database\Factories;

use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TableFactory extends Factory
{
    protected $model = EloquentTable::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa ' . fake()->numberBetween(1, 100),
        ];
    }
}
