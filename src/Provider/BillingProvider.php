<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;

interface BillingProvider
{
    public function create(Subscription $subscription, array $options = []): BillingProviderResult;

    public function swapItemPrice(
        Subscription     $subscription,
        SubscriptionItem $subscriptionItemId,
        string           $newPriceId,
        array            $options = []
    ): BillingProviderResult;

    public function cancel(Subscription $subscription, bool $atPeriodEnd, array $options): BillingProviderResult;

    public function pause(Subscription $subscription, array $options): BillingProviderResult;

    public function resume(Subscription $subscription, array $options): BillingProviderResult;
}