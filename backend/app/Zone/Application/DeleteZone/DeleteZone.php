<?php

declare(strict_types=1);

namespace App\Zone\Application\DeleteZone;

use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class DeleteZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id, string $restaurantId): void
    {
        $idVO = Uuid::create($id);
        $restaurantIdVO = Uuid::create($restaurantId);

        $zone = $this->zoneRepository->findById($idVO, $restaurantIdVO);

        if ($zone === null) {
            throw ZoneNotFoundException::forId($idVO);
        }

        $this->zoneRepository->delete($idVO, $restaurantIdVO);
    }
}
