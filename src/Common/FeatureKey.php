<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;
use Stringable;

final readonly class FeatureKey implements Stringable
{
    private function __construct(
        private string $value
    )
    {
        if (!preg_match('/^[a-z0-9_]+$/', $this->value)) {
            throw new InvalidArgumentException('Feature key should only contain lowercase letters, numbers and underscores');
        }
    }

    public static function fromString(string $value): FeatureKey
    {
        return new self($value);
    }

    public static function hydrate(mixed $value): FeatureKey
    {
        return FeatureKey::fromString((string) $value);
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