<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use Stringable;
use Symfony\Component\Uid\Ulid;

final readonly class SubscriptionItemEntitlementId implements Stringable
{
    public function __construct(private Ulid $value)
    {
    }

    public static function generate(): self
    {
        return new self(new Ulid());
    }

    public static function hydrate(mixed $value): self
    {
        return SubscriptionItemEntitlementId::fromString((string) $value);
    }

    public static function fromString(string $value): self
    {
        return new self(Ulid::fromString($value));
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function equals(SubscriptionItemEntitlementId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}