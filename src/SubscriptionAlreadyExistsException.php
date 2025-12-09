<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class SubscriptionAlreadyExistsException extends RuntimeException
{
    public static function forLogicalName(string $logicalName): self
    {
        return new self("Subscription for logical name \"$logicalName\" already exists");
    }
}