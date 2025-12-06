<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use Stringable;
use Symfony\Component\Uid\Ulid;

final readonly class ProductPriceId implements Stringable
{
    private function __construct(private Ulid $value)
    {
    }

    public static function generate(): ProductPriceId
    {
        return new ProductPriceId(new Ulid());
    }

    public static function fromString(string $value): ProductPriceId
    {
        return new ProductPriceId(Ulid::fromString($value));
    }

    public static function hydrate(mixed $value): ProductPriceId
    {
        return ProductPriceId::fromString((string) $value);
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(ProductPriceId $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}