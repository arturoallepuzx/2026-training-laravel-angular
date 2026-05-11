<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductFamilyNotFoundException;
use App\Product\Domain\Exception\ProductTaxNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentProductRepository implements ProductRepositoryInterface
{
    /** @var array<string, int|null> */
    private array $familyInternalIds = [];

    /** @var array<string, int|null> */
    private array $taxInternalIds = [];

    public function __construct(
        private EloquentProduct $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function create(Product $product): void
    {
        $familyInternalId = $this->familyInternalId($product->familyId(), $product->restaurantId());
        $taxInternalId = $this->taxInternalId($product->taxId(), $product->restaurantId());

        if ($familyInternalId === null) {
            throw ProductFamilyNotFoundException::forId($product->familyId());
        }

        if ($taxInternalId === null) {
            throw ProductTaxNotFoundException::forId($product->taxId());
        }

        $this->model->newQuery()->create([
            'uuid' => $product->id()->value(),
            'restaurant_id' => $this->restaurantIdResolver->toInternalId($product->restaurantId()),
            'family_id' => $familyInternalId,
            'tax_id' => $taxInternalId,
            'image_src' => $product->imageSrc()?->value(),
            'name' => $product->name()->value(),
            'price' => $product->price()->value(),
            'stock' => $product->stock()->value(),
            'active' => $product->active(),
            'created_at' => $product->createdAt()->value(),
            'updated_at' => $product->updatedAt()->value(),
        ]);
    }

    public function update(Product $product): void
    {
        $familyInternalId = $this->familyInternalId($product->familyId(), $product->restaurantId());
        $taxInternalId = $this->taxInternalId($product->taxId(), $product->restaurantId());

        if ($familyInternalId === null) {
            throw ProductFamilyNotFoundException::forId($product->familyId());
        }

        if ($taxInternalId === null) {
            throw ProductTaxNotFoundException::forId($product->taxId());
        }

        $this->model->newQuery()
            ->where('uuid', $product->id()->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($product->restaurantId()))
            ->update([
                'family_id' => $familyInternalId,
                'tax_id' => $taxInternalId,
                'image_src' => $product->imageSrc()?->value(),
                'name' => $product->name()->value(),
                'price' => $product->price()->value(),
                'stock' => $product->stock()->value(),
                'active' => $product->active(),
                'updated_at' => $product->updatedAt()->value(),
            ]);
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Product
    {
        $model = $this->productQuery($restaurantId)
            ->where('products.uuid', $id->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    public function findByNameAndRestaurantId(ProductName $name, Uuid $restaurantId): ?Product
    {
        $model = $this->productQuery($restaurantId)
            ->where('products.name', $name->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    /** @return Product[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->productQuery($restaurantId)->get();

        return $models->map(fn (EloquentProduct $model) => $this->toDomainEntity($model, $restaurantId))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    /** @return Builder<EloquentProduct> */
    private function productQuery(Uuid $restaurantId): Builder
    {
        return $this->model->newQuery()
            ->select('products.*', 'families.uuid as family_uuid', 'taxes.uuid as tax_uuid')
            ->join('families', 'families.id', '=', 'products.family_id')
            ->join('taxes', 'taxes.id', '=', 'products.tax_id')
            ->where('products.restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId));
    }

    private function familyInternalId(Uuid $familyId, Uuid $restaurantId): ?int
    {
        $key = $this->cacheKey($familyId, $restaurantId);

        if (array_key_exists($key, $this->familyInternalIds)) {
            return $this->familyInternalIds[$key];
        }

        $id = $this->model->getConnection()
            ->table('families')
            ->where('uuid', $familyId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->value('id');

        return $this->familyInternalIds[$key] = $id !== null ? (int) $id : null;
    }

    private function taxInternalId(Uuid $taxId, Uuid $restaurantId): ?int
    {
        $key = $this->cacheKey($taxId, $restaurantId);

        if (array_key_exists($key, $this->taxInternalIds)) {
            return $this->taxInternalIds[$key];
        }

        $id = $this->model->getConnection()
            ->table('taxes')
            ->where('uuid', $taxId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->value('id');

        return $this->taxInternalIds[$key] = $id !== null ? (int) $id : null;
    }

    private function cacheKey(Uuid $id, Uuid $restaurantId): string
    {
        return $restaurantId->value().':'.$id->value();
    }

    private function toDomainEntity(EloquentProduct $model, Uuid $restaurantId): Product
    {
        return Product::fromPersistence(
            (string) $model->uuid,
            $restaurantId->value(),
            (string) $model->getAttribute('family_uuid'),
            (string) $model->getAttribute('tax_uuid'),
            $model->image_src !== null ? (string) $model->image_src : null,
            (string) $model->name,
            (int) $model->price,
            (int) $model->stock,
            (bool) $model->active,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
