<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\EloquentRestaurantIdResolver;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): EloquentUserRepository
    {
        return new EloquentUserRepository(new EloquentUser, new EloquentRestaurantIdResolver);
    }

    public function test_find_by_email_returns_user_when_exists_in_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'email' => 'user@example.com',
            'role' => 'supervisor',
        ]);

        $user = $this->repository()->findByEmail(
            Email::create('user@example.com'),
            Uuid::create($restaurant->uuid),
        );

        $this->assertNotNull($user);
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame($restaurant->uuid, $user->restaurantId()->value());
        $this->assertTrue($user->role()->isSupervisor());
    }

    public function test_find_by_email_returns_null_when_email_belongs_to_different_restaurant(): void
    {
        $restaurantA = EloquentRestaurant::factory()->create();
        $restaurantB = EloquentRestaurant::factory()->create();
        EloquentUser::factory()->create([
            'restaurant_id' => $restaurantA->id,
            'email' => 'crosstenant@example.com',
        ]);

        $user = $this->repository()->findByEmail(
            Email::create('crosstenant@example.com'),
            Uuid::create($restaurantB->uuid),
        );

        $this->assertNull($user);
    }

    public function test_find_by_email_returns_null_when_email_does_not_exist(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $user = $this->repository()->findByEmail(
            Email::create('missing@example.com'),
            Uuid::create($restaurant->uuid),
        );

        $this->assertNull($user);
    }
}
