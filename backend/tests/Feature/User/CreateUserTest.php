<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_users_returns_201_and_user_json(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/users", [
            'role' => 'admin',
            'name' => 'Integration User',
            'email' => 'integration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pin' => '1234',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'restaurant_id',
            'role',
            'name',
            'email',
            'image_src',
            'created_at',
            'updated_at',
        ]);
        $response->assertJson([
            'restaurant_id' => $restaurant->uuid,
            'role' => 'admin',
            'name' => 'Integration User',
            'email' => 'integration@example.com',
        ]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('id')
        );

        $this->assertDatabaseHas('users', [
            'email' => 'integration@example.com',
            'role' => 'admin',
            'name' => 'Integration User',
        ]);
    }
}
