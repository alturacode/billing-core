<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

enum BillingProviderResultStatus
{
    case Success;
    case Failure;
    case RequiresAction;
}