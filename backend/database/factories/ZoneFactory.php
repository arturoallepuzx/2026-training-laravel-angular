<?php

namespace Database\Factories;

use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ZoneFactory extends Factory
{
    protected $model = EloquentZone::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'Terraza',
        ];
    }
}
