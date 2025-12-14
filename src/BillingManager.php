<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\Common\Billable;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;

final readonly class BillingManager
{
    public function __construct(
        private ProductRepository       $products,
        private SubscriptionRepository  $subscriptions,
        private BillingProviderRegistry $provider
    )
    {
    }

    public function swapSubscriptionItemPrice(string $subscriptionItemId, string $newPriceId, array $providerOptions = []): BillingProviderResult
    {
        $subscription = $this->subscriptions->findByItemId(SubscriptionItemId::fromString($subscriptionItemId));
        $product = $this->products->findByPriceId(ProductPriceId::fromString($newPriceId));

        if ($subscription === null) {
            throw new SubscriptionNotFoundException();
        }

        if ($product === null) {
            throw new ProductNotFoundException();
        }

        $subscriptionItem = $subscription->findItem(SubscriptionItemId::fromString($subscriptionItemId));
        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->swapItemPrice($subscription, $subscriptionItem, $newPriceId, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }

    /**
     * @param SubscriptionDraft $draft
     * @param array $providerOptions
     * @return BillingProviderResult
     */
    public function createSubscription(SubscriptionDraft $draft, array $providerOptions = []): BillingProviderResult
    {
        $subscription = $this->subscriptions->findForBillable(
            Billable::fromString($draft->billableType, $draft->billableId),
            SubscriptionName::fromString($draft->name),
        );

        if ($subscription && $subscription->isActive()) {
            throw SubscriptionAlreadyExistsException::forLogicalName($draft->name);
        }

        $subscription = new SubscriptionFactory()->fromProductListAndDraft($this->products->all(), $draft);
        $gateway = $this->provider->subscriptionProviderFor($draft->provider);
        $result = $gateway->create($subscription, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }

    public function cancelSubscription(
        string $subscriptionId,
        bool   $atPeriodEnd = true,
        array  $providerOptions = []
    ): BillingProviderResult
    {
        $subscription = $this->subscriptions->find(SubscriptionId::fromString($subscriptionId));

        if ($subscription === null) {
            throw new SubscriptionNotFoundException();
        }

        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->cancel($subscription, $atPeriodEnd, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }

    public function pauseSubscription(
        string $subscriptionId,
        array  $providerOptions = []
    ): BillingProviderResult
    {
        $subscription = $this->subscriptions->find(SubscriptionId::fromString($subscriptionId));

        if ($subscription === null) {
            throw new SubscriptionNotFoundException();
        }

        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->pause($subscription, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }

    public function resumeSubscription(
        string $subscriptionId,
        array  $providerOptions = []
    ): BillingProviderResult
    {
        $subscription = $this->subscriptions->find(SubscriptionId::fromString($subscriptionId));

        if ($subscription === null) {
            throw new SubscriptionNotFoundException();
        }

        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->resume($subscription, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }
}