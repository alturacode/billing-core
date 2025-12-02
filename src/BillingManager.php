<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\Uid\Ulid;

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
     * @param string $productId
     * @param string $provider
     * @param int $quantity
     * @param DateTimeImmutable|null $trialEndsAt
     * @param array<array{productId: string, priceId: string, quantity: int}> $addons
     * @param array $providerOptions
     * @return BillingProviderResult
     */
    public function createSubscription(
        string             $name,
        string             $customerId,
        string             $productId,
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

        $product = $this->products->findByPriceId(new ProductPriceId(new Ulid($productId)));
        if ($product === null) {
            throw new RuntimeException('Product not found');
        }

        if ($product->kind() !== ProductKind::Plan) {
            throw new RuntimeException('Primary product must be a plan.');
        }

        $price = $product->findPrice(new ProductPriceId(new Ulid($productId)));

        $subscription = Subscription::create(
            name: new SubscriptionName($name),
            customerId: new SubscriptionCustomerId($customerId),
            productId: $product->id(),
            productPriceId: $price->id(),
            provider: new SubscriptionProvider($provider),
            quantity: $quantity,
            trialEndsAt: $trialEndsAt,
            items: array_map(fn($addon) => [
                'productId' => new ProductId($addon['productId']),
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
        $subscription = $this->subscriptions->find(new SubscriptionId(new Ulid($subscriptionId)));

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
        $subscription = $this->subscriptions->find(new SubscriptionId(new Ulid($subscriptionId)));

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
        $subscription = $this->subscriptions->find(new SubscriptionId(new Ulid($subscriptionId)));

        if ($subscription === null) {
            throw new RuntimeException('Subscription not found');
        }

        $gateway = $this->provider->subscriptionProviderFor($subscription->provider()->value());
        $result = $gateway->resume($subscription, $providerOptions);
        $this->subscriptions->save($result->subscription);

        return $result;
    }
}