<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use RuntimeException;

/**
 * Billing provider that executes all operations synchronously, useful for testing or as a default provider.
 */
final readonly class SynchronousBillingProvider implements
    BillingProvider,
    SwappableItemPriceBillingProvider,
    PausableBillingProvider,
    ProductAwareBillingProvider,
    CustomerAwareBillingProvider
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

    public function syncProduct(Product $product, array $options = []): ProductSyncResult
    {
        return ProductSyncResult::makeEmpty();
    }

    public function syncProducts(array $products, array $options = []): ProductSyncResult
    {
        return ProductSyncResult::makeEmpty();
    }

    public function syncCustomer(BillableIdentity $billable, ?BillableDetails $details = null, array $options = []): CustomerSyncResult
    {
        return CustomerSyncResult::completed($billable->id());
    }
}