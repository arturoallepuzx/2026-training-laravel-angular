<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\QueryException;

class EloquentUserRepository implements UserRepositoryInterface
{
    private const SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = '23000';

    public function __construct(
        private EloquentUser $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function create(User $user): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $user->id()->value(),
                'restaurant_id' => $this->restaurantIdResolver->toInternalId($user->restaurantId()),
                'role' => $user->role()->value(),
                'image_src' => $user->imageSrc(),
                'name' => $user->name()->value(),
                'email' => $user->email()->value(),
                'password' => $user->passwordHash()->value(),
                'pin' => $user->pinHash()?->value(),
                'created_at' => $user->createdAt()->value(),
                'updated_at' => $user->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === self::SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION) {
                throw UserEmailAlreadyExistsException::forEmail($user->email()->value());
            }

            throw $e;
        }
    }

    public function update(User $user): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $user->id()->value())
                ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($user->restaurantId()))
                ->update([
                    'role' => $user->role()->value(),
                    'image_src' => $user->imageSrc(),
                    'name' => $user->name()->value(),
                    'email' => $user->email()->value(),
                    'password' => $user->passwordHash()->value(),
                    'pin' => $user->pinHash()?->value(),
                    'updated_at' => $user->updatedAt()->value(),
                ]);
        } catch (QueryException $e) {
            if ($e->getCode() === self::SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION) {
                throw UserEmailAlreadyExistsException::forEmail($user->email()->value());
            }

            throw $e;
        }
    }

    public function delete(Uuid $id, Uuid $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->delete();
    }

    public function findById(Uuid $id, Uuid $restaurantId): ?User
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

    public function findByEmail(Email $email, Uuid $restaurantId): ?User
    {
        $model = $this->model->newQuery()
            ->where('email', $email->value())
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model, $restaurantId);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->model->newQuery()
            ->where('email', $email->value())
            ->exists();
    }

    public function existsByEmailExcludingId(Email $email, Uuid $excludeUserId): bool
    {
        return $this->model->newQuery()
            ->where('email', $email->value())
            ->where('uuid', '!=', $excludeUserId->value())
            ->exists();
    }

    public function findAllByRestaurantId(Uuid $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $this->restaurantIdResolver->toInternalId($restaurantId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (EloquentUser $model): User => $this->toDomainEntity($model, $restaurantId))
            ->all();
    }

    private function toDomainEntity(EloquentUser $model, Uuid $restaurantId): User
    {
        return User::fromPersistence(
            $model->uuid,
            $restaurantId->value(),
            $model->role,
            $model->name,
            $model->email,
            $model->password,
            $model->pin,
            $model->image_src,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
