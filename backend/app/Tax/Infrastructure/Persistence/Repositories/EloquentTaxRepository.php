<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function create(Tax $tax): void
    {
        $this->model->newQuery()->create([
            'uuid' => $tax->id()->value(),
            'restaurant_id' => $this->restaurantIdResolver->toInternalId($tax->restaurantId()),
            'name' => $tax->name()->value(),
            'percentage' => $tax->percentage()->value(),
            'created_at' => $tax->createdAt()->value(),
            'updated_at' => $tax->updatedAt()->value(),
        ]);
    }

    public function update(Tax $tax): void
    {
        $this->model->newQuery()
            ->where('uuid', $tax->id()->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($tax->restaurantId()))
            ->update([
                'name' => $tax->name()->value(),
                'percentage' => $tax->percentage()->value(),
                'updated_at' => $tax->updatedAt()->value(),
            ]);
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Tax
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

    public function findByNameAndRestaurantId(TaxName $name, Uuid $restaurantId): ?Tax
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

    /** @return Tax[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->model->newQuery()
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->get();

        return $models->map(fn (EloquentTax $model) => $this->toDomainEntity($model, $restaurantId))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    private function toDomainEntity(EloquentTax $model, Uuid $restaurantId): Tax
    {
        return Tax::fromPersistence(
            $model->uuid,
            $restaurantId->value(),
            $model->name,
            $model->percentage,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
