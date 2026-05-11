<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Persistence\Models;

use Database\Factories\TableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'zone_id',
        'name',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): Factory
    {
        return TableFactory::new();
    }
}
