<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;

interface SwappableItemPriceBillingProvider
{
    public function swapItemPrice(
        Subscription     $subscription,
        SubscriptionItem $subscriptionItem,
        string           $newPriceId,
        array            $options = []
    ): BillingProviderResult;
}