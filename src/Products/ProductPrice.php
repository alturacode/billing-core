<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Money;

final readonly class ProductPrice
{
    private function __construct(
        private ProductPriceId       $id,
        private Money                $price,
        private ProductPriceInterval $interval,
    )
    {
    }

    public static function hydrate(array $data): self
    {
        return new self(
            ProductPriceId::fromString($data['id']),
            Money::hydrate($data['price']),
            ProductPriceInterval::hydrate($data['interval'])
        );
    }

    public function id(): ProductPriceId
    {
        return $this->id;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function interval(): ProductPriceInterval
    {
        return $this->interval;
    }
}