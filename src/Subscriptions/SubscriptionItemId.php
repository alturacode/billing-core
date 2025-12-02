<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use Stringable;
use Symfony\Component\Uid\Ulid;

final readonly class SubscriptionItemId implements Stringable
{
    public function __construct(private Ulid $value)
    {
    }

    public static function generate(): self
    {
        return new self(new Ulid());
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(SubscriptionItemId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}