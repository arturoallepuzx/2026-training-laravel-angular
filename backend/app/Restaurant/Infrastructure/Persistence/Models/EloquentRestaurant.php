<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Persistence\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentRestaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'restaurants';

    protected $fillable = [
        'uuid',
        'name',
        'legal_name',
        'tax_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function newFactory(): Factory
    {
        return RestaurantFactory::new();
    }
}
