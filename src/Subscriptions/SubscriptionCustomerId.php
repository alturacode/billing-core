<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use Stringable;

final readonly class SubscriptionCustomerId implements Stringable
{
    public function __construct(private string $value)
    {
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