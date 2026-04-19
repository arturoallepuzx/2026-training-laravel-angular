<?php

namespace App\Tax\Infrastructure\Persistence\Models;

use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentTax extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxes';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'name',
        'percentage',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'percentage' => 'integer',
    ];

    protected static function newFactory(): Factory
    {
        return TaxFactory::new();
    }
}
