<?php

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPrice;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Products\ProductSlug;
use AlturaCode\Billing\Core\Provider\SynchronousBillingProvider;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\Money;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Products\ProductFeature;

it('swaps an item price', function () {
    // Arrange
    $productRepository = $this->createMock(ProductRepository::class);
    $provider = new SynchronousBillingProvider($productRepository);

    $subscriptionId = SubscriptionId::generate();
    $billable = BillableIdentity::fromString('user', '1');
    $subscriptionName = SubscriptionName::fromString('main');
    $subscriptionProvider = SubscriptionProvider::fromString('test');

    $oldPriceId = ProductPriceId::generate();
    $newPriceId = ProductPriceId::generate();

    $item = SubscriptionItem::create(
        id: SubscriptionItemId::generate(),
        priceId: $oldPriceId,
        quantity: 1,
        price: Money::hydrate(['amount' => 1000, 'currency' => 'usd']),
        interval: ProductPriceInterval::monthly(),
    );

    $subscription = Subscription::create(
        $subscriptionId,
        $subscriptionName,
        $billable,
        $subscriptionProvider,
        null
    )->withItems($item);

    $newPrice = ProductPrice::create(
        $newPriceId,
        Money::hydrate(['amount' => 2000, 'currency' => 'usd']),
        ProductPriceInterval::monthly()
    );

    $product = Product::create(
        ProductId::generate(),
        ProductKind::Plan,
        ProductSlug::fromString('test_product'),
        'Test Product',
        'Description'
    )->withPrices($newPrice);

    $productRepository->method('findByPriceId')
        ->with($this->callback(fn($arg) => $arg->equals($newPriceId)))
        ->willReturn($product);

    // Act
    $result = $provider->swapItemPrice($subscription, $item, (string) $newPriceId);

    // Assert
    expect($result->subscription->findItem($item->id())->priceId()->equals($newPriceId))->toBeTrue()
        ->and($result->subscription->findItem($item->id())->price()->amount())->toBe(2000);
});

it('swaps an item price and updates entitlements', function () {
    // Arrange
    $productRepository = $this->createMock(ProductRepository::class);
    $provider = new SynchronousBillingProvider($productRepository);

    $subscriptionId = SubscriptionId::generate();
    $billable = BillableIdentity::fromString('user', '1');
    $subscriptionName = SubscriptionName::fromString('main');
    $subscriptionProvider = SubscriptionProvider::fromString('test');

    $oldPriceId = ProductPriceId::generate();
    $newPriceId = ProductPriceId::generate();

    $item = SubscriptionItem::create(
        id: SubscriptionItemId::generate(),
        priceId: $oldPriceId,
        quantity: 1,
        price: Money::hydrate(['amount' => 1000, 'currency' => 'usd']),
        interval: ProductPriceInterval::monthly(),
    );

    $subscription = Subscription::create(
        $subscriptionId,
        $subscriptionName,
        $billable,
        $subscriptionProvider,
        null
    )->withItems($item);

    $newPrice = ProductPrice::create(
        $newPriceId,
        Money::hydrate(['amount' => 2000, 'currency' => 'usd']),
        ProductPriceInterval::monthly()
    );

    $product = Product::create(
        ProductId::generate(),
        ProductKind::Plan,
        ProductSlug::fromString('test_product'),
        'Test Product',
        'Description'
    )->withPrices($newPrice)->withFeatures(
        ProductFeature::create(FeatureKey::fromString('posts'), FeatureValue::limit(10))
    );

    $productRepository->method('findByPriceId')
        ->with($this->callback(fn($arg) => $arg->equals($newPriceId)))
        ->willReturn($product);

    // Act
    $result = $provider->swapItemPrice($subscription, $item, (string) $newPriceId);

    // Assert
    $updatedItem = $result->subscription->findItem($item->id());
    expect($updatedItem->priceId()->equals($newPriceId))->toBeTrue()
        ->and($updatedItem->price()->amount())->toBe(2000)
        ->and(count($updatedItem->entitlements()))->toBe(1)
        ->and($updatedItem->entitlements()[0]->key()->value())->toBe('posts')
        ->and($updatedItem->entitlements()[0]->value()->value())->toBe(10);
});
