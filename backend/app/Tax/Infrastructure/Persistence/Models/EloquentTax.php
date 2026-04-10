<?php

namespace App\Tax\Infrastructure\Persistence\Models;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(EloquentRestaurant::class, 'restaurant_id');
    }

    protected static function newFactory(): Factory
    {
        return TaxFactory::new();
    }
}
