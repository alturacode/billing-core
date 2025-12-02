<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use InvalidArgumentException;
use Stringable;

final readonly class SubscriptionProvider implements Stringable
{
    public function __construct(private string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Subscription provider cannot be empty');
        }

        if (preg_match('/[^a-zA-Z0-9_]/', $this->value)) {
            throw new InvalidArgumentException('Subscription provider should only contain lowercase letters, numbers and underscores');
        }
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