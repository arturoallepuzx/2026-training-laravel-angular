<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPinHash;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    private const VALID_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private const VALID_PIN_HASH = '$2y$10$e0NRiAABtmjItdqEGaKYGeqexOPHSjbgWQLJFfh6jUSGH/nVgqUUG';

    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $restaurantId = Uuid::generate();
        $role = UserRole::admin();
        $name = UserName::create('Test User');
        $email = Email::create('user@example.com');
        $passwordHash = PasswordHash::create(self::VALID_HASH);
        $pinHash = UserPinHash::create(self::VALID_PIN_HASH);

        $user = User::dddCreate($restaurantId, $role, $name, $email, $passwordHash, $pinHash, 'avatar.png');

        $this->assertSame($restaurantId->value(), $user->restaurantId()->value());
        $this->assertTrue($user->role()->isAdmin());
        $this->assertSame('Test User', $user->name()->value());
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame(self::VALID_HASH, $user->passwordHash()->value());
        $this->assertSame(self::VALID_PIN_HASH, $user->pinHash()?->value());
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

        $this->assertNull($user->pinHash());
        $this->assertNull($user->imageSrc());
    }

    public function test_is_not_modified_after_creation(): void
    {
        $user = $this->buildUser();

        $this->assertFalse($user->wasModified());
    }

    public function test_updates_name(): void
    {
        $user = $this->buildUser();

        $user->updateName(UserName::create('Renamed User'));

        $this->assertSame('Renamed User', $user->name()->value());
        $this->assertTrue($user->wasModified());
    }

    public function test_same_values_do_not_mark_as_modified(): void
    {
        $user = $this->buildUser();

        $user->updateName(UserName::create('Test User'));
        $user->updateEmail(Email::create('user@example.com'));
        $user->updateRole(UserRole::operator());
        $user->updateImageSrc('avatar.png');

        $this->assertFalse($user->wasModified());
    }

    private function buildUser(): User
    {
        return User::dddCreate(
            Uuid::generate(),
            UserRole::operator(),
            UserName::create('Test User'),
            Email::create('user@example.com'),
            PasswordHash::create(self::VALID_HASH),
            null,
            'avatar.png',
        );
    }
}
