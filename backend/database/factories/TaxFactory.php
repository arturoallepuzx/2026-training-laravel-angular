<?php

namespace Database\Factories;

use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaxFactory extends Factory
{
    protected $model = EloquentTax::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA General',
            'percentage' => 21,
        ];
    }
}
