<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use Stringable;
use Symfony\Component\Uid\Ulid;

final readonly class SubscriptionId implements Stringable
{
    private function __construct(private Ulid $value)
    {
    }

    public static function generate(): SubscriptionId
    {
        return new SubscriptionId(new Ulid());
    }

    public static function hydrate(mixed $value): self
    {
        return SubscriptionId::fromString((string) $value);
    }

    public static function fromString(string $value): SubscriptionId
    {
        return new SubscriptionId(Ulid::fromString($value));
    }

    public function value(): string
    {
        return $this->value->toString();
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}