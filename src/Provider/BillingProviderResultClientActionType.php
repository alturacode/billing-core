<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

enum BillingProviderResultClientActionType
{
    case None;
    case Redirect;

    public function isRedirect(): bool
    {
        return $this === self::Redirect;
    }

    public function isNone(): bool
    {
        return $this === self::None;
    }
}