<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Persistence\Models;

use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'families';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'name',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return FamilyFactory::new();
    }
}
