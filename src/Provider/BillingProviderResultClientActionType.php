<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

enum BillingProviderResultClientActionType
{
    case None;
    case Redirect;
}