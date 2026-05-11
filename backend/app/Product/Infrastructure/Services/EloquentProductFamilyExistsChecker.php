<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Services;

use App\Product\Domain\Interfaces\ProductFamilyExistsCheckerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use Illuminate\Support\Facades\DB;

class EloquentProductFamilyExistsChecker implements ProductFamilyExistsCheckerInterface
{
    public function __construct(
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function check(Uuid $familyId, Uuid $restaurantId): bool
    {
        return DB::table('families')
            ->where('uuid', $familyId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->exists();
    }
}
