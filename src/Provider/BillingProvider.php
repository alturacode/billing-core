<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;

interface BillingProvider
{
    public function create(Subscription $subscription, array $options = []): BillingProviderResult;
    public function cancel(Subscription $subscription, bool $atPeriodEnd, array $options): BillingProviderResult;
}