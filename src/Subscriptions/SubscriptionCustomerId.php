<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use InvalidArgumentException;
use Stringable;

final readonly class SubscriptionCustomerId implements Stringable
{
    public function __construct(private mixed $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Subscription customer id cannot be empty');
        }

        if (!is_string($this->value) && !is_int($this->value)) {
            throw new InvalidArgumentException('Subscription customer id should be a string or integer');
        }
    }

    public static function hydrate(mixed $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): SubscriptionCustomerId
    {
        return new self($value);
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}