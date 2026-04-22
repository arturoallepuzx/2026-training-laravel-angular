<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Persistence\Models;

use Database\Factories\ZoneFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zones';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'name',
    ];

    protected static function newFactory(): Factory
    {
        return ZoneFactory::new();
    }
}
