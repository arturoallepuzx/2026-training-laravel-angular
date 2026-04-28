<?php

declare(strict_types=1);

namespace App\User\Application\RefreshAuthentication;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;

class RefreshAuthentication
{
    public function __construct(
        private UserAuthenticationRefresherInterface $userAuthenticationRefresher,
    ) {}

    public function __invoke(string $restaurantId, string $refreshCredential): RefreshAuthenticationResponse
    {
        $issuedAuthentication = $this->userAuthenticationRefresher->refreshForRestaurant(
            Uuid::create($restaurantId),
            $refreshCredential,
        );

        return RefreshAuthenticationResponse::create($issuedAuthentication);
    }
}
