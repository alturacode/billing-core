<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use Stringable;
use Symfony\Component\Uid\Ulid;

final readonly class ProductId implements Stringable
{
    public function __construct(private Ulid $value)
    {
    }

    public static function generate(): ProductId
    {
        return new ProductId(new Ulid());
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(ProductPriceId $other): bool
    {
        return $this->value->equals($other->value());
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}