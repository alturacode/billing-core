<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

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

    public static function fromString(string $value): ProductId
    {
        return new ProductId(Ulid::fromString($value));
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(ProductId $other): bool
    {
        return $this->value->equals($other->value());
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}