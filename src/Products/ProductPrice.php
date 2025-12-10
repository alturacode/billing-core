<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Common\Money;
use InvalidArgumentException;
use Symfony\Component\Uid\Ulid;

final readonly class ProductPrice
{
    private function __construct(
        private ProductPriceId       $id,
        private Money                $price,
        private ProductPriceInterval $interval,
    )
    {
    }

    public static function hydrate(mixed $data): self
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('ProductPrice::hydrate expects an array.');
        }

        return new self(
            ProductPriceId::hydrate($data['id']),
            Money::hydrate($data['price']),
            ProductPriceInterval::hydrate($data['interval'])
        );
    }

    public static function create(ProductPriceId $id, Money $price, ProductPriceInterval $interval): self
    {
        return new self($id, $price, $interval);
    }

    public static function monthly(ProductPriceId $id, Money $price): self
    {
        return new self($id, $price, ProductPriceInterval::monthly());
    }

    public static function yearly(ProductPriceId $id, Money $price): self
    {
        return new self($id, $price, ProductPriceInterval::yearly());
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