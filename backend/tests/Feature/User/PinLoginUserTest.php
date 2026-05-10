<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class PinLoginUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_pin_login_returns_json_and_sets_refresh_cookie_when_pin_is_valid(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role' => 'operator',
            'name' => 'Waiter',
            'email' => 'waiter-pin@example.com',
            'pin' => Hash::make('1234'),
        ]);

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/auth/pin-login", [
            'user_id' => $user->uuid,
            'pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'id' => $user->uuid,
                'restaurant_id' => $restaurant->uuid,
                'role' => 'operator',
                'name' => 'Waiter',
                'email' => 'waiter-pin@example.com',
                'image_src' => null,
            ],
        ]);
        $this->assertIsString($response->json('access_token'));

        $refreshCookie = $this->findCookie($response->headers->getCookies(), 'refresh_token');

        $this->assertNotNull($refreshCookie);
        $this->assertTrue($refreshCookie->isHttpOnly());
        $this->assertSame('/api/restaurants/'.$restaurant->uuid.'/auth', $refreshCookie->getPath());
    }

    public function test_post_pin_login_returns_401_when_pin_is_invalid(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'pin' => Hash::make('1234'),
        ]);

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/auth/pin-login", [
            'user_id' => $user->uuid,
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_pin_login_returns_401_when_user_belongs_to_other_restaurant(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $otherRestaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'pin' => Hash::make('1234'),
        ]);

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/auth/pin-login", [
            'user_id' => $user->uuid,
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_pin_login_returns_401_when_user_has_no_pin(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();
        $user = EloquentUser::factory()->create([
            'restaurant_id' => $restaurant->id,
            'pin' => null,
        ]);

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/auth/pin-login", [
            'user_id' => $user->uuid,
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_pin_login_returns_422_when_payload_is_invalid(): void
    {
        $restaurant = EloquentRestaurant::factory()->create();

        $response = $this->postJson("/api/restaurants/{$restaurant->uuid}/auth/pin-login", [
            'user_id' => 'not-a-uuid',
            'pin' => '12',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'details' => ['user_id', 'pin'],
        ]);
    }

    /**
     * @param  list<Cookie>  $cookies
     */
    private function findCookie(array $cookies, string $name): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }
}
