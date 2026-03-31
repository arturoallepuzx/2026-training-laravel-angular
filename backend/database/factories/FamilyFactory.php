<?php

namespace Database\Factories;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FamilyFactory extends Factory
{
    protected $model = EloquentFamily::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->word(),
            'active' => true,
        ];
    }
}
