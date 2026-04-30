<?php

declare(strict_types=1);

namespace App\User\Application\GetAuthenticatedUser;

use App\User\Domain\Entity\User;

final readonly class GetAuthenticatedUserResponse
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public string $role,
        public string $name,
        public string $email,
        public ?string $imageSrc,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            restaurantId: $user->restaurantId()->value(),
            role: $user->role()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            imageSrc: $user->imageSrc(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->id,
                'restaurant_id' => $this->restaurantId,
                'role' => $this->role,
                'name' => $this->name,
                'email' => $this->email,
                'image_src' => $this->imageSrc,
            ],
        ];
    }
}
