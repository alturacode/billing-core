<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionCustomerId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use DateTimeImmutable;
use RuntimeException;

final readonly class BillingManager
{
    public function __construct(
        private ProductRepository       $products,
        private SubscriptionRepository  $subscriptions,
        private BillingProviderRegistry $provider
    )
    {
    }

    /**
     * @param string $name
     * @param string $customerId
     * @param string $priceId
     * @param string $provider
     * @param int $quantity
     * @param DateTimeImmutable|null $trialEndsAt
     * @param array<array{priceId: string, quantity: int}> $addons
     * @param array $providerOptions
     * @return BillingProviderResult
     */
    public function createSubscription(
        string             $name,
        string             $customerId,
        string             $priceId,
        string             $provider,
        int                $quantity = 1,
        ?DateTimeImmutable $trialEndsAt = null,
        array              $addons = [],
        array              $providerOptions = []
    ): BillingProviderResult
    {
        $subscription = $this->subscriptions->findForCustomer(
            new SubscriptionCustomerId($customerId),
            new SubscriptionName($name),
        );

        if ($subscription && $subscription->isActive()) {
            throw new RuntimeException('Subscription already exists.');
        }

        $products = $this->products->findMultipleByPriceIds([
            PRoductPriceId::fromString($priceId),
            ...array_map(fn($addon) => ProductPriceId::fromString($addon['priceId']), $addons),
        ]);

        $primaryProduct = array_find($products, fn(Product $product) => $product->id()->equals(
            ProductId::fromString($priceId)
        ));

        if ($primaryProduct === null) {
            throw new RuntimeException(sprintf('Product with price ID %s not found', $priceId));
        }

        if ($primaryProduct->kind() !== ProductKind::Plan) {
            throw new RuntimeException('Primary product must be a plan.');
        }

        $primaryPrice = $primaryProduct->findPrice(ProductPriceId::fromString($priceId));

        // Ensure all addons are defined in some product
        foreach ($addons as $addon) {
            $product = array_find($products, fn(Product $product) => $product->hasPrice(ProductPriceId::fromString($addon['priceId'])));
            if ($product === null) {
                throw new RuntimeException(sprintf('Product with price ID %s not found', $addon['priceId']));
            }

            // Ensure addon price currency is the same as primary price currency
            if ($product->findPrice(ProductPriceId::fromString($addon['priceId']))->price()->currency()->equals($primaryPrice->price()->currency()) === false) {
                throw new RuntimeException(sprintf('Addon price currency must match primary price currency. Addon price ID: %s', $addon['priceId']));
            }
        }

        $subscription = Subscription::create(
            name: new SubscriptionName($name),
            customerId: new SubscriptionCustomerId($customerId),
            productPriceId: $primaryPrice->id(),
            price: $primaryPrice->price(),
            provider: new SubscriptionProvider($provider),
            quantity: $quantity,
            trialEndsAt: $trialEndsAt,
            items: array_map(fn($addon) => [
                'priceId' => new ProductPriceId($addon['priceId']),
                'quantity' => $addon['quantity'],
            ], $addons)
        ); // status = Incomplete

        $gateway = $this->provider->subscriptionProviderFor($provider);
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
            throw new RuntimeException('Subscription not found');
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
            throw new RuntimeException('Subscription not found');
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
            throw new RuntimeException('Subscription not found');
        }

        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->resume($subscription, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }
}