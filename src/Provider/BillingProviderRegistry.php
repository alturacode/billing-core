<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface BillingProviderRegistry
{
    public function subscriptionProviderFor(string $provider): BillingProvider;
}