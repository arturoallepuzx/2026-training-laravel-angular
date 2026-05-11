<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function create(Family $family): void
    {
        $this->model->newQuery()->create([
            'uuid' => $family->id()->value(),
            'restaurant_id' => $this->restaurantIdResolver->toInternalId($family->restaurantId()),
            'name' => $family->name()->value(),
            'active' => $family->active(),
            'created_at' => $family->createdAt()->value(),
            'updated_at' => $family->updatedAt()->value(),
        ]);
    }

    public function update(Family $family): void
    {
        $this->model->newQuery()
            ->where('uuid', $family->id()->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($family->restaurantId()))
            ->update([
                'name' => $family->name()->value(),
                'active' => $family->active(),
                'updated_at' => $family->updatedAt()->value(),
            ]);
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Family
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

    public function findByNameAndRestaurantId(FamilyName $name, Uuid $restaurantId): ?Family
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

    /** @return Family[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->model->newQuery()
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->get();

        return $models->map(fn (EloquentFamily $model) => $this->toDomainEntity($model, $restaurantId))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    private function toDomainEntity(EloquentFamily $model, Uuid $restaurantId): Family
    {
        return Family::fromPersistence(
            $model->uuid,
            $restaurantId->value(),
            $model->name,
            (bool) $model->active,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
