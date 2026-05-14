<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Services;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Table\Domain\Interfaces\TableZoneExistsCheckerInterface;
use Illuminate\Support\Facades\DB;

class EloquentTableZoneExistsChecker implements TableZoneExistsCheckerInterface
{
    public function __construct(
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function check(Uuid $zoneId, Uuid $restaurantId): bool
    {
        return DB::table('zones')
            ->where('uuid', $zoneId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->exists();
    }
}
