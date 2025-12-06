<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use InvalidArgumentException;
use Stringable;

final readonly class SubscriptionProvider implements Stringable
{
    private function __construct(private string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Subscription provider cannot be empty');
        }

        if (preg_match('/[^a-zA-Z0-9_]/', $this->value)) {
            throw new InvalidArgumentException('Subscription provider should only contain lowercase letters, numbers and underscores');
        }
    }

    public static function hydrate(mixed $value): self
    {
        return new self((string) $value);
    }

    public static function fromString(string $value): SubscriptionProvider
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