<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use InvalidArgumentException;
use Stringable;

/**
 * Logical name of the subscription, for example, "main" or "default".
 */
final readonly class SubscriptionName implements Stringable
{
    private function __construct(private string $value)
    {
        if ($this->value === '') {
            throw new InvalidArgumentException('Subscription name cannot be empty');
        }

        if (preg_match('/[^a-z0-9_]/', $this->value)) {
            throw new InvalidArgumentException('Subscription name should only contain lowercase letters, numbers and underscores');
        }
    }

    public static function hydrate(mixed $value): self
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('SubscriptionName::hydrate expects a string.');
        }

        return new self(trim($value));
    }

    public static function fromString(string $value): SubscriptionName
    {
        return new self(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(SubscriptionName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}