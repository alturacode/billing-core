<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use InvalidArgumentException;
use Stringable;

final readonly class FeatureKey implements Stringable
{
    public function __construct(
        private string $value
    )
    {
        if (!preg_match('/^[a-z0-9_]+$/', $this->value)) {
            throw new InvalidArgumentException('Feature key should only contain lowercase letters, numbers and underscores');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(FeatureKey $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}