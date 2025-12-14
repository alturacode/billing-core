<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class BillingProviderMissingCapabilityException extends RuntimeException
{
    public static function make(string $provider, string $capability): self
    {
        return new self("Billing provider \"$provider\" is missing required capability \"$capability\"");
    }
}