<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductFeature;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId;
use RuntimeException;

/**
 * Billing provider that executes all operations synchronously, useful for testing or as a default provider.
 *
 * @codeCoverageIgnore
 */
final readonly class SynchronousBillingProvider implements
    BillingProvider,
    SwappableItemPriceBillingProvider,
    PausableBillingProvider,
    ProductAwareBillingProvider,
    CustomerAwareBillingProvider
{
    public function __construct(
        private ProductRepository $productRepository
    )
    {
    }

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
        $newPriceId = ProductPriceId::fromString($newPriceId);
        $product = $this->productRepository->findByPriceId($newPriceId);
        $price = $product->findPrice($newPriceId);

        $entitlements = array_map(
            fn(ProductFeature $feature) => SubscriptionItemEntitlement::create(
                SubscriptionItemEntitlementId::generate(),
                $feature->key(),
                $feature->value()
            ),
            $product->features()
        );

        return BillingProviderResult::completed(
            $subscription->changeItemPrice(
                $subscriptionItem->id(),
                $newPriceId,
                $price->price(),
                $price->interval(),
                $entitlements
            )
        );
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