<?php

namespace App\User\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\RestaurantIdResolverInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EloquentUser $model,
        private RestaurantIdResolverInterface $restaurantIdResolver,
    ) {}

    public function create(User $user): void
    {
        $this->model->newQuery()->create([
            'uuid' => $user->id()->value(),
            'restaurant_id' => $this->restaurantIdResolver->toInternalId($user->restaurantId()),
            'role' => $user->role()->value(),
            'image_src' => $user->imageSrc(),
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password' => $user->passwordHash()->value(),
            'pin' => $user->pin()?->value(),
            'created_at' => $user->createdAt()->value(),
            'updated_at' => $user->updatedAt()->value(),
        ]);
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
