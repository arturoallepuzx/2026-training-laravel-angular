<?php

namespace App\Sale\Infrastructure\Persistence\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'order_id',
        'user_id',
        'ticket_number',
        'value_date',
        'total',
    ];

    protected $casts = [
        'ticket_number' => 'integer',
        'total' => 'integer',
        'value_date' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return SaleFactory::new();
    }
}
