<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Exception\ZoneNameAlreadyExistsException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\QueryException;

class EloquentZoneRepository implements ZoneRepositoryInterface
{
    private const UNIQUE_ZONE_NAME_CONSTRAINT = 'zones_restaurant_name_active_unique';

    public function __construct(
        private EloquentZone $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(Zone $zone): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $zone->id()->value(),
                'restaurant_id' => $this->restaurantIdResolver->toInternalId($zone->restaurantId()),
                'name' => $zone->name()->value(),
                'created_at' => $zone->createdAt()->value(),
                'updated_at' => $zone->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_ZONE_NAME_CONSTRAINT)) {
                throw ZoneNameAlreadyExistsException::forName($zone->name()->value());
            }

            throw $e;
        }
    }

    public function update(Zone $zone): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $zone->id()->value())
                ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($zone->restaurantId()))
                ->update([
                    'name' => $zone->name()->value(),
                    'updated_at' => $zone->updatedAt()->value(),
                ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_ZONE_NAME_CONSTRAINT)) {
                throw ZoneNameAlreadyExistsException::forName($zone->name()->value());
            }

            throw $e;
        }
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Zone
    {
        $model = $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    public function findByNameAndRestaurantId(ZoneName $name, Uuid $restaurantId): ?Zone
    {
        $model = $this->model->newQuery()
            ->where('name', $name->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    /** @return Zone[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->model->newQuery()
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->get();

        return $models->map(fn (EloquentZone $model) => $this->toDomainEntity($model, $restaurantId))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    private function toDomainEntity(EloquentZone $model, Uuid $restaurantId): Zone
    {
        return Zone::fromPersistence(
            $model->uuid,
            $restaurantId->value(),
            $model->name,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
