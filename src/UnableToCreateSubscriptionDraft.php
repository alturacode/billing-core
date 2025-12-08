<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class UnableToCreateSubscriptionDraft extends RuntimeException
{
    public static function missingRequiredProperty(string $property): self
    {
        return new self("Missing required property '$property'");
    }
}