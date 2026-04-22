<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'role',
        'image_src',
        'name',
        'email',
        'password',
        'pin',
    ];

    protected $hidden = [
        'password',
        'pin',
    ];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
