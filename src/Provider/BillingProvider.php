<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;

interface BillingProvider
{
    public function create(Subscription $draft, array $options = []): BillingProviderResult;

    public function cancel(Subscription $subscription, bool $atPeriodEnd, array $options): BillingProviderResult;

    public function pause(Subscription $subscription, array $options): BillingProviderResult;

    public function resume(Subscription $subscription, array $options): BillingProviderResult;
}