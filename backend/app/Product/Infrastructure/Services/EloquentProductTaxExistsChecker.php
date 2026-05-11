<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Services;

use App\Product\Domain\Interfaces\ProductTaxExistsCheckerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use Illuminate\Support\Facades\DB;

class EloquentProductTaxExistsChecker implements ProductTaxExistsCheckerInterface
{
    public function __construct(
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function check(Uuid $taxId, Uuid $restaurantId): bool
    {
        return DB::table('taxes')
            ->where('uuid', $taxId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->exists();
    }
}
