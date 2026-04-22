<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Database\Factories\OrderLineFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentOrderLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_lines';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'order_id',
        'product_id',
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
        return OrderLineFactory::new();
    }
}
