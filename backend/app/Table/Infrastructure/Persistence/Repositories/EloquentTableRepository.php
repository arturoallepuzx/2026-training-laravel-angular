<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\Exception\TableNameAlreadyExistsException;
use App\Table\Domain\Exception\TableZoneNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

class EloquentTableRepository implements TableRepositoryInterface
{
    private const UNIQUE_TABLE_CONSTRAINT = 'tables_restaurant_zone_name_active_unique';

    /** @var array<string, int|null> */
    private array $zoneInternalIds = [];

    public function __construct(
        private EloquentTable $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(Table $table): void
    {
        $zoneInternalId = $this->zoneInternalId($table->zoneId(), $table->restaurantId());

        if ($zoneInternalId === null) {
            throw TableZoneNotFoundException::forId($table->zoneId());
        }

        try {
            $this->model->newQuery()->create([
                'uuid' => $table->id()->value(),
                'restaurant_id' => $this->restaurantIdResolver->toInternalId($table->restaurantId()),
                'zone_id' => $zoneInternalId,
                'name' => $table->name()->value(),
                'created_at' => $table->createdAt()->value(),
                'updated_at' => $table->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_TABLE_CONSTRAINT)) {
                throw TableNameAlreadyExistsException::forName($table->name()->value());
            }

            throw $e;
        }
    }

    public function update(Table $table): void
    {
        $zoneInternalId = $this->zoneInternalId($table->zoneId(), $table->restaurantId());

        if ($zoneInternalId === null) {
            throw TableZoneNotFoundException::forId($table->zoneId());
        }

        try {
            $this->model->newQuery()
                ->where('uuid', $table->id()->value())
                ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($table->restaurantId()))
                ->update([
                    'zone_id' => $zoneInternalId,
                    'name' => $table->name()->value(),
                    'updated_at' => $table->updatedAt()->value(),
                ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_TABLE_CONSTRAINT)) {
                throw TableNameAlreadyExistsException::forName($table->name()->value());
            }

            throw $e;
        }
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?Table
    {
        $model = $this->tableQuery($restaurantId)
            ->where('tables.uuid', $id->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    public function findByNameAndZoneIdAndRestaurantId(
        TableName $name,
        Uuid $zoneId,
        Uuid $restaurantId,
    ): ?Table {
        $zoneInternalId = $this->zoneInternalId($zoneId, $restaurantId);

        if ($zoneInternalId === null) {
            return null;
        }

        $model = $this->tableQuery($restaurantId)
            ->where('tables.zone_id', $zoneInternalId)
            ->where('tables.name', $name->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    /** @return Table[] */
    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        $models = $this->tableQuery($restaurantId)->get();

        return $models->map(fn (EloquentTable $model) => $this->toDomainEntity($model, $restaurantId))->all();
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    /** @return Builder<EloquentTable> */
    private function tableQuery(Uuid $restaurantId): Builder
    {
        $internalRestaurantId = $this->restaurantIdResolver->toInternalId($restaurantId);

        return $this->model->newQuery()
            ->select('tables.*', 'zones.uuid as zone_uuid')
            ->join('zones', 'zones.id', '=', 'tables.zone_id')
            ->where('tables.restaurant_id', $internalRestaurantId)
            ->where('zones.restaurant_id', $internalRestaurantId)
            ->whereNull('zones.deleted_at');
    }

    private function zoneInternalId(Uuid $zoneId, Uuid $restaurantId): ?int
    {
        $key = $restaurantId->value().':'.$zoneId->value();

        if (array_key_exists($key, $this->zoneInternalIds)) {
            return $this->zoneInternalIds[$key];
        }

        $id = $this->model->getConnection()
            ->table('zones')
            ->where('uuid', $zoneId->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->whereNull('deleted_at')
            ->value('id');

        return $this->zoneInternalIds[$key] = $id !== null ? (int) $id : null;
    }

    private function toDomainEntity(EloquentTable $model, Uuid $restaurantId): Table
    {
        return Table::fromPersistence(
            (string) $model->uuid,
            $restaurantId->value(),
            (string) $model->getAttribute('zone_uuid'),
            (string) $model->name,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
