<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use InvalidArgumentException;
use Stringable;

final readonly class ProductSlug implements Stringable
{
    private function __construct(
        private string $value
    )
    {
        if (!preg_match('/^[a-z0-9_]+$/', $this->value)) {
            throw new InvalidArgumentException('Plan slug should only contain lowercase letters, numbers and underscores');
        }
    }

    public static function fromString(string $value): ProductSlug
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}