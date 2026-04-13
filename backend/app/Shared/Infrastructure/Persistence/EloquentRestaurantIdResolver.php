<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentRestaurantIdResolver implements RestaurantIdResolverInterface
{
    /** @var array<string, int> */
    private array $cache = [];

    public function toInternalId(Uuid $restaurantUuid): int
    {
        $uuid = $restaurantUuid->value();

        if (isset($this->cache[$uuid])) {
            return $this->cache[$uuid];
        }

        $id = EloquentRestaurant::query()
            ->where('uuid', $uuid)
            ->value('id');

        if ($id === null) {
            throw (new ModelNotFoundException())->setModel(EloquentRestaurant::class, [$uuid]);
        }

        return $this->cache[$uuid] = (int) $id;
    }
}
