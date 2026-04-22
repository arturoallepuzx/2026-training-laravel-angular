<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Database\Factories\SaleLineFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSaleLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_lines';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'order_line_id',
        'user_id',
        'quantity',
        'price',
        'tax_percentage',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'integer',
        'tax_percentage' => 'integer',
    ];

    protected static function newFactory(): Factory
    {
        return SaleLineFactory::new();
    }
}
