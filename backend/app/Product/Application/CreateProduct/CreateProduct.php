<?php

declare(strict_types=1);

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductFamilyNotFoundException;
use App\Product\Domain\Exception\ProductNameAlreadyExistsException;
use App\Product\Domain\Exception\ProductTaxNotFoundException;
use App\Product\Domain\Interfaces\ProductFamilyExistsCheckerInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\Interfaces\ProductTaxExistsCheckerInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductFamilyExistsCheckerInterface $productFamilyExistsChecker,
        private ProductTaxExistsCheckerInterface $productTaxExistsChecker,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $familyId,
        string $taxId,
        ?string $imageSrc,
        string $name,
        int $price,
        int $stock,
        bool $active = true,
    ): CreateProductResponse {
        $restaurantUuid = Uuid::create($restaurantId);
        $familyUuid = Uuid::create($familyId);
        $taxUuid = Uuid::create($taxId);
        $productName = ProductName::create($name);

        if (! $this->productFamilyExistsChecker->check($familyUuid, $restaurantUuid)) {
            throw ProductFamilyNotFoundException::forId($familyUuid);
        }

        if (! $this->productTaxExistsChecker->check($taxUuid, $restaurantUuid)) {
            throw ProductTaxNotFoundException::forId($taxUuid);
        }

        if ($this->productRepository->findByNameAndRestaurantId($productName, $restaurantUuid) !== null) {
            throw ProductNameAlreadyExistsException::forName($productName->value());
        }

        $product = Product::dddCreate(
            $restaurantUuid,
            $familyUuid,
            $taxUuid,
            $imageSrc !== null ? ProductImageSrc::create($imageSrc) : null,
            $productName,
            ProductPrice::create($price),
            ProductStock::create($stock),
            $active,
        );

        $this->productRepository->create($product);

        return CreateProductResponse::create($product);
    }
}
