<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;

interface PausableBillingProvider extends BillingProvider
{
    public function pause(Subscription $subscription, array $options): BillingProviderResult;

    public function resume(Subscription $subscription, array $options): BillingProviderResult;
}