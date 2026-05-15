<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProduct;

use App\Product\Domain\Exception\ProductFamilyNotFoundException;
use App\Product\Domain\Exception\ProductNameAlreadyExistsException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductTaxNotFoundException;
use App\Product\Domain\Interfaces\ProductFamilyExistsCheckerInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\Interfaces\ProductTaxExistsCheckerInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductFamilyExistsCheckerInterface $productFamilyExistsChecker,
        private ProductTaxExistsCheckerInterface $productTaxExistsChecker,
    ) {}

    public function __invoke(
        string $id,
        string $restaurantId,
        ?string $familyId,
        ?string $taxId,
        ?string $imageSrc,
        bool $imageSrcWasProvided,
        ?string $name,
        ?int $price,
        ?int $stock,
        ?bool $active,
    ): UpdateProductResponse {
        $productId = Uuid::create($id);
        $restaurantUuid = Uuid::create($restaurantId);

        $product = $this->productRepository->findById($productId, $restaurantUuid);

        if ($product === null) {
            throw ProductNotFoundException::forId($productId);
        }

        if ($familyId !== null) {
            $familyUuid = Uuid::create($familyId);

            if ($familyUuid->value() !== $product->familyId()->value()) {
                if (! $this->productFamilyExistsChecker->check($familyUuid, $restaurantUuid)) {
                    throw ProductFamilyNotFoundException::forId($familyUuid);
                }

                $product->updateFamilyId($familyUuid);
            }
        }

        if ($taxId !== null) {
            $taxUuid = Uuid::create($taxId);

            if ($taxUuid->value() !== $product->taxId()->value()) {
                if (! $this->productTaxExistsChecker->check($taxUuid, $restaurantUuid)) {
                    throw ProductTaxNotFoundException::forId($taxUuid);
                }

                $product->updateTaxId($taxUuid);
            }
        }

        $productName = $name !== null ? ProductName::create($name) : null;

        if ($productName !== null && $productName->value() !== $product->name()->value()) {
            if (! $productName->equals($product->name())) {
                $existing = $this->productRepository->findByNameAndRestaurantId($productName, $restaurantUuid);

                if ($existing !== null && $existing->id()->value() !== $product->id()->value()) {
                    throw ProductNameAlreadyExistsException::forName($productName->value());
                }
            }

            $product->updateName($productName);
        }

        if ($imageSrcWasProvided) {
            $product->updateImageSrc($imageSrc !== null ? ProductImageSrc::create($imageSrc) : null);
        }

        if ($price !== null) {
            $product->updatePrice(ProductPrice::create($price));
        }

        if ($stock !== null) {
            $product->updateStock(ProductStock::create($stock));
        }

        if ($active !== null) {
            $product->updateActive($active);
        }

        if ($product->wasModified()) {
            $this->productRepository->update($product);
        }

        return UpdateProductResponse::create($product);
    }
}
