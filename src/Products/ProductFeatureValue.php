<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use InvalidArgumentException;

final readonly class ProductFeatureValue
{
    private function __construct(
        private mixed $value
    )
    {
        if ($this->value === null || $this->value === '') {
            throw new InvalidArgumentException('Feature value cannot be null or empty');
        }

        if (is_numeric($this->value) && $this->value < 0) {
            throw new InvalidArgumentException('Feature value cannot be negative');
        }
    }

    public static function hydrate(mixed $data): self
    {
        return new self($data);
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    public function isBoolean(): bool
    {
        return is_bool($this->value);
    }
}