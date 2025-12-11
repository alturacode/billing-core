<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class UnableToCreateSubscriptionDraftException extends RuntimeException
{
    public static function missingRequiredProperty(string $property): self
    {
        return new self("Missing required property '$property'");
    }

    public static function missingPlanPriceIdentifier(): self
    {
        return new self("Missing plan price identifier. You must provide either a plan price id or plan slug with currency and interval information.");
    }
}