<?php

namespace App\Order\Infrastructure\Persistence\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'status',
        'table_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'diners',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'diners' => 'integer',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }
}
