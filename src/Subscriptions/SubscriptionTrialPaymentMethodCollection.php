<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

enum SubscriptionTrialPaymentMethodCollection: string
{
    case Required = 'required';
    case Optional = 'optional';

    public static function fromBool(bool $required): self
    {
        return $required ? self::Required : self::Optional;
    }

    public function requiresPaymentMethod(): bool
    {
        return $this === self::Required;
    }
}
