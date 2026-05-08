<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\DeleteUser\DeleteUser;
use Illuminate\Http\Response;

class DeleteController
{
    public function __construct(
        private DeleteUser $deleteUser,
    ) {}

    public function __invoke(string $restaurantId, string $userId): Response
    {
        ($this->deleteUser)($userId, $restaurantId);

        return new Response('', 204);
    }
}
