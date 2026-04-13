<?php

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function save(Tax $tax): void
    {
        $existing = $this->model->newQuery()->where('uuid', $tax->id()->value())->first();

        if ($existing !== null) {
            $existing->update([
                'name' => $tax->name()->value(),
                'percentage' => $tax->percentage()->value(),
                'updated_at' => $tax->updatedAt()->value(),
            ]);

            return;
        }

        $this->model->newQuery()->create([
            'uuid' => $tax->id()->value(),
            'restaurant_id' => $this->restaurantIdResolver->toInternalId($tax->restaurantId()),
            'name' => $tax->name()->value(),
            'percentage' => $tax->percentage()->value(),
            'created_at' => $tax->createdAt()->value(),
            'updated_at' => $tax->updatedAt()->value(),
        ]);
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Tax
    {
        $model = $this->model->newQuery()
            ->with('restaurant')
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /** @return Tax[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->model->newQuery()
            ->with('restaurant')
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->get();

        return $models->map(fn (EloquentTax $model) => $this->toDomainEntity($model))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    private function toDomainEntity(EloquentTax $model): Tax
    {
        return Tax::fromPersistence(
            $model->uuid,
            $model->restaurant->uuid,
            $model->name,
            $model->percentage,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
