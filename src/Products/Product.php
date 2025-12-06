<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use InvalidArgumentException;
use RuntimeException;

final readonly class Product
{
    private function __construct(
        private ProductId   $id,
        private ProductKind $kind,
        private ProductSlug $slug,
        private string      $name,
        private string      $description,
        /** @var ProductPrice[] $prices */
        private array       $prices,
        /** @var ProductFeature[] $features */
        private array       $features
    )
    {
        $this->assertValid();
    }

    public static function hydrate(array $data): self
    {
        return new self(
            ProductId::hydrate($data['id']),
            ProductKind::from($data['kind']),
            ProductSlug::hydrate($data['slug']),
            $data['name'],
            $data['description'],
            array_map(fn($price) => ProductPrice::hydrate($price), $data['prices']),
            array_map(fn($feature) => ProductFeature::hydrate($feature), $data['features'])
        );
    }

    public function withPrices(ProductPrice ...$prices): self
    {
        return new self($this->id, $this->kind, $this->slug, $this->name, $this->description, $prices, $this->features);
    }

    public function withFeatures(ProductFeature ...$features): self
    {
        return new self($this->id, $this->kind, $this->slug, $this->name, $this->description, $this->prices, $features);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function kind(): ProductKind
    {
        return $this->kind;
    }

    public function slug(): ProductSlug
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function features(): array
    {
        return $this->features;
    }

    public function prices(): array
    {
        return $this->prices;
    }

    public function hasPrice(ProductPriceId $productPriceId): bool
    {
        return array_find($this->prices, fn($price) => $price->id()->equals($productPriceId)) !== false;
    }

    public function hasAnyPrice(ProductPriceId ...$productPriceIds): bool
    {
        return array_reduce($productPriceIds,
            fn($carry, $productPriceId) => $carry || array_any($this->prices,
                    fn($price) => $price->id()->equals($productPriceId)), false);
    }

    public function findPrice(ProductPriceId $productPriceId): ProductPrice
    {
        foreach ($this->prices as $price) {
            if ($price->id()->equals($productPriceId)) {
                return $price;
            }
        }
        throw new RuntimeException('Product price not found');
    }

    private function assertValid(): void
    {
        // assert prices are an instance of PlanPrice
        foreach ($this->prices as $price) {
            if (!$price instanceof ProductPrice) {
                throw new InvalidArgumentException('Plan prices must be instances of PlanPrice');
            }
        }

        // assert features are an instance of PlanFeature
        foreach ($this->features as $feature) {
            if (!$feature instanceof ProductFeature) {
                throw new InvalidArgumentException('Plan features must be instances of PlanFeature');
            }
        }
    }
}