<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Persistence\Repositories;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Exception\RestaurantEmailAlreadyExistsException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use Illuminate\Database\QueryException;

class EloquentRestaurantRepository implements RestaurantRepositoryInterface
{
    private const UNIQUE_RESTAURANT_EMAIL_CONSTRAINT = 'restaurants_email_unique';

    public function __construct(
        private EloquentRestaurant $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function create(Restaurant $restaurant): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $restaurant->id()->value(),
                'name' => $restaurant->name()->value(),
                'legal_name' => $restaurant->legalName()->value(),
                'tax_id' => $restaurant->taxId()->value(),
                'email' => $restaurant->email()->value(),
                'password' => null,
                'created_at' => $restaurant->createdAt()->value(),
                'updated_at' => $restaurant->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_RESTAURANT_EMAIL_CONSTRAINT)) {
                throw RestaurantEmailAlreadyExistsException::forEmail($restaurant->email()->value());
            }

            throw $e;
        }
    }

    public function update(Restaurant $restaurant): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $restaurant->id()->value())
                ->update([
                    'name' => $restaurant->name()->value(),
                    'legal_name' => $restaurant->legalName()->value(),
                    'tax_id' => $restaurant->taxId()->value(),
                    'email' => $restaurant->email()->value(),
                    'updated_at' => $restaurant->updatedAt()->value(),
                ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_RESTAURANT_EMAIL_CONSTRAINT)) {
                throw RestaurantEmailAlreadyExistsException::forEmail($restaurant->email()->value());
            }

            throw $e;
        }
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->delete();
    }

    public function findById(Uuid $id): ?Restaurant
    {
        $model = $this->model->newQuery()
            ->where('uuid', $id->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByEmail(Email $email): ?Restaurant
    {
        $model = $this->model->newQuery()
            ->where('email', $email->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findAll(): array
    {
        return $this->model->newQuery()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (EloquentRestaurant $model): Restaurant => $this->toDomainEntity($model))
            ->all();
    }

    private function toDomainEntity(EloquentRestaurant $model): Restaurant
    {
        return Restaurant::fromPersistence(
            $model->uuid,
            $model->name,
            $model->legal_name,
            $model->tax_id,
            $model->email,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
