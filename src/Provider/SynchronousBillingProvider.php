<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use RuntimeException;

/**
 * Billing provider that executes all operations synchronously, useful for testing or as a default provider.
 */
final readonly class SynchronousBillingProvider implements
    BillingProvider,
    SwappableItemPriceBillingProvider,
    PausableBillingProvider
{
    public function create(Subscription $subscription, array $options = []): BillingProviderResult
    {
        return BillingProviderResult::completed($subscription->activate());
    }

    public function swapItemPrice(
        Subscription     $subscription,
        SubscriptionItem $subscriptionItem,
        string           $newPriceId,
        array            $options = []
    ): BillingProviderResult
    {
        throw new RuntimeException('Not implemented');
    }

    public function cancel(Subscription $subscription, bool $atPeriodEnd, array $options): BillingProviderResult
    {
        return BillingProviderResult::completed($subscription->cancel($atPeriodEnd));
    }

    public function pause(Subscription $subscription, array $options): BillingProviderResult
    {
        return BillingProviderResult::completed($subscription->pause());
    }

    public function resume(Subscription $subscription, array $options): BillingProviderResult
    {
        return BillingProviderResult::completed($subscription->resume());
    }
}