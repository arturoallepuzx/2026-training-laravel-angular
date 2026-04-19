<?php

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPin;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    private const VALID_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $restaurantId = Uuid::generate();
        $role = UserRole::admin();
        $name = UserName::create('Test User');
        $email = Email::create('user@example.com');
        $passwordHash = PasswordHash::create(self::VALID_HASH);
        $pin = UserPin::create('1234');

        $user = User::dddCreate($restaurantId, $role, $name, $email, $passwordHash, $pin, 'avatar.png');

        $this->assertSame($restaurantId->value(), $user->restaurantId()->value());
        $this->assertTrue($user->role()->isAdmin());
        $this->assertSame('Test User', $user->name()->value());
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame(self::VALID_HASH, $user->passwordHash()->value());
        $this->assertSame('1234', $user->pin()?->value());
        $this->assertSame('avatar.png', $user->imageSrc());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $user->id()->value()
        );
        $this->assertInstanceOf(DomainDateTime::class, $user->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $user->updatedAt());
    }

    public function test_ddd_create_allows_null_pin_and_image(): void
    {
        $user = User::dddCreate(
            Uuid::generate(),
            UserRole::operator(),
            UserName::create('No Pin User'),
            Email::create('nopin@example.com'),
            PasswordHash::create(self::VALID_HASH),
        );

        $this->assertNull($user->pin());
        $this->assertNull($user->imageSrc());
    }
}
