<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Money;

final readonly class ProductPrice
{
    public function __construct(
        private ProductPriceId       $id,
        private Money                $price,
        private ProductPriceInterval $interval,
    )
    {
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