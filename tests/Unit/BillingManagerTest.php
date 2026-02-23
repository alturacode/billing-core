<?php

use AlturaCode\Billing\Core\BillableDetailsResolver;
use AlturaCode\Billing\Core\BillingManager;
use AlturaCode\Billing\Core\BillingProviderMissingCapabilityException;
use AlturaCode\Billing\Core\ProductNotFoundException;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\BillingProvider;
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\Provider\CustomerAwareBillingProvider;
use AlturaCode\Billing\Core\Provider\PausableBillingProvider;
use AlturaCode\Billing\Core\Provider\ProductAwareBillingProvider;
use AlturaCode\Billing\Core\Provider\ProductSyncResult;
use AlturaCode\Billing\Core\Provider\SwappableItemPriceBillingProvider;
use AlturaCode\Billing\Core\SubscriptionAlreadyExistsException;
use AlturaCode\Billing\Core\SubscriptionDraft;
use AlturaCode\Billing\Core\SubscriptionNotFoundException;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    $this->products = $this->createMock(ProductRepository::class);
    $this->subscriptions = $this->createMock(SubscriptionRepository::class);
    $this->providerRegistry = $this->createMock(BillingProviderRegistry::class);
    $this->billingProvider = $this->createMock(BillingProvider::class);
    $this->billingDetailsResolver = $this->createMock(BillableDetailsResolver::class);

    $this->manager = new BillingManager(
        $this->products,
        $this->subscriptions,
        $this->providerRegistry,
        $this->billingDetailsResolver
    );
});

function hydrateSubscription(string $status = 'active'): Subscription
{
    $itemId = (string)new Ulid();
    return Subscription::hydrate([
        'id' => (string)new Ulid(),
        'billable' => ['type' => 'user', 'id' => 'user_1'],
        'provider' => 'stripe',
        'name' => 'default',
        'status' => $status,
        'items' => [
            [
                'id' => $itemId,
                'price_id' => (string)new Ulid(),
                'quantity' => 1,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1],
                'current_period_starts_at' => '2023-01-01 00:00:00',
                'current_period_ends_at' => '2023-02-01 00:00:00',
            ]
        ],
        'primary_item_id' => $itemId,
        'created_at' => '2023-01-01 00:00:00',
        'cancel_at_period_end' => false,
        'trial_ends_at' => null,
        'canceled_at' => null,
    ]);
}

it('creates a new subscription successfully', function () {
    $priceId = (string)new Ulid();

    $draft = new SubscriptionDraft(
        name: 'default',
        billableId: 'user_1',
        billableType: 'user',
        provider: 'stripe',
        priceId: $priceId
    );

    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'pro_plan',
        'name' => 'Pro Plan',
        'description' => 'Best plan',
        'prices' => [
            [
                'id' => $priceId,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1]
            ]
        ],
        'features' => []
    ]);

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn(null);

    $this->products->expects($this->once())
        ->method('all')
        ->willReturn([$product]);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->billingProvider->expects($this->once())
        ->method('create')
        ->willReturnCallback(fn($sub) => BillingProviderResult::completed($sub));

    $this->subscriptions->expects($this->once())
        ->method('save');

    $result = $this->manager->createSubscription($draft);

    expect($result)->toBeInstanceOf(BillingProviderResult::class)
        ->and($result->subscription->billable()->id())->toBe('user_1')
        ->and($result->subscription->items()[0]->priceId()->value())->toBe($priceId);
});

it('throws exception if subscription already exists and is active when creating', function () {
    $priceId = (string)new Ulid();
    $draft = new SubscriptionDraft(
        name: 'default',
        billableId: 'user_1',
        billableType: 'user',
        provider: 'stripe',
        priceId: $priceId
    );

    $existingSubscription = hydrateSubscription('active');

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn($existingSubscription);

    $this->manager->createSubscription($draft);
})->throws(SubscriptionAlreadyExistsException::class, 'Subscription for logical name "default" already exists');

it('cancels an existing subscription', function () {
    $subId = (string)new Ulid();
    $subscription = hydrateSubscription('active');

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($this->callback(fn($arg) => $arg instanceof SubscriptionId && $arg->value() === $subId))
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->billingProvider->expects($this->once())
        ->method('cancel')
        ->with($subscription, true, [])
        ->willReturn(BillingProviderResult::completed($subscription));

    $this->subscriptions->expects($this->once())
        ->method('save')
        ->with($subscription);

    $result = $this->manager->cancelSubscription($subId);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('throws exception if subscription not found when canceling', function () {
    $subId = (string)new Ulid();

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->willReturn(null);

    $this->manager->cancelSubscription($subId);
})->throws(SubscriptionNotFoundException::class);

it('pauses an existing subscription', function () {
    $subId = (string)new Ulid();
    $subscription = hydrateSubscription('active');
    $pausableBillingProvider = $this->createMock(PausableBillingProvider::class);

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($pausableBillingProvider);

    $pausableBillingProvider->expects($this->once())
        ->method('pause')
        ->with($subscription, [])
        ->willReturn(BillingProviderResult::completed($subscription));

    $this->subscriptions->expects($this->once())
        ->method('save')
        ->with($subscription);

    $result = $this->manager->pauseSubscription($subId);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('throws exception if subscription not found when pausing', function () {
    $subId = (string)new Ulid();
    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($subId)
        ->willReturn(null);

    $this->manager->pauseSubscription($subId);
})->throws(SubscriptionNotFoundException::class);

it('resumes an existing subscription', function () {
    $subId = (string)new Ulid();
    $subscription = hydrateSubscription('paused');
    $pausableBillingProvider = $this->createMock(PausableBillingProvider::class);

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($pausableBillingProvider);

    $pausableBillingProvider->expects($this->once())
        ->method('resume')
        ->with($subscription, [])
        ->willReturn(BillingProviderResult::completed($subscription));

    $this->subscriptions->expects($this->once())
        ->method('save')
        ->with($subscription);

    $result = $this->manager->resumeSubscription($subId);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('throws exception if subscription not found when resuming', function () {
    $subId = (string)new Ulid();
    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($subId)
        ->willReturn(null);

    $this->manager->resumeSubscription($subId);
})->throws(SubscriptionNotFoundException::class);


use AlturaCode\Billing\Core\Common\BillableDetails;

it('swaps subscription item price successfully', function () {
    $subscription = hydrateSubscription('active');
    $itemId = $subscription->items()[0]->id()->value();
    $newPriceId = (string) new Ulid();

    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'basic_plan',
        'name' => 'Basic Plan',
        'description' => 'Basic',
        'prices' => [
            [
                'id' => $newPriceId,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1]
            ]
        ],
        'features' => []
    ]);

    $this->subscriptions->expects($this->once())
        ->method('findByItemId')
        ->with($this->callback(fn($arg) => $arg instanceof SubscriptionItemId && $arg->value() === $itemId))
        ->willReturn($subscription);

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn($product);

    $swappable = $this->createMock(SwappableItemPriceBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($swappable);

    $swappable->expects($this->once())
        ->method('swapItemPrice')
        ->with($subscription, $this->callback(fn($i) => $i->id()->value() === $itemId), $newPriceId, [])
        ->willReturn(BillingProviderResult::completed($subscription));

    $this->subscriptions->expects($this->once())
        ->method('save')
        ->with($subscription);

    $result = $this->manager->swapSubscriptionItemPrice($itemId, $newPriceId);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('throws exception if subscription item not found when swapping', function () {
    $itemId = (string) new Ulid();
    $newPriceId = (string) new Ulid();

    $this->subscriptions->expects($this->once())
        ->method('findByItemId')
        ->willReturn(null);

    $this->manager->swapSubscriptionItemPrice($itemId, $newPriceId);
})->throws(SubscriptionNotFoundException::class);

it('throws exception if provider missing capability when swapping', function () {
    $subscription = hydrateSubscription('active');
    $itemId = $subscription->items()[0]->id()->value();
    $newPriceId = (string) new Ulid();

    $this->subscriptions->expects($this->once())
        ->method('findByItemId')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider); // lacks capability

    $this->manager->swapSubscriptionItemPrice($itemId, $newPriceId);
})->throws(BillingProviderMissingCapabilityException::class);

it('throws ProductNotFoundException when swapping with unknown price', function () {
    $subscription = hydrateSubscription('active');
    $itemId = $subscription->items()[0]->id()->value();
    $newPriceId = (string) new Ulid();

    $this->subscriptions->expects($this->once())
        ->method('findByItemId')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->createMock(SwappableItemPriceBillingProvider::class));

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn(null);

    $this->manager->swapSubscriptionItemPrice($itemId, $newPriceId);
})->throws(ProductNotFoundException::class);

it('syncs customer details when provider is customer-aware on creation', function () {
    $priceId = (string) new Ulid();
    $draft = new SubscriptionDraft(
        name: 'default',
        billableId: 'user_1',
        billableType: 'user',
        provider: 'stripe',
        priceId: $priceId
    );

    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'pro_plan',
        'name' => 'Pro Plan',
        'description' => 'Best plan',
        'prices' => [
            [
                'id' => $priceId,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1]
            ]
        ],
        'features' => []
    ]);

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn(null);

    $this->products->expects($this->once())
        ->method('all')
        ->willReturn([$product]);

    $customerAware = $this->createMock(CustomerAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($customerAware);

    $details = BillableDetails::from(displayName: 'John Doe', email: 'john@example.com');
    $this->billingDetailsResolver->expects($this->once())
        ->method('resolve')
        ->with($this->callback(fn($id) => $id->id() === 'user_1' && $id->type() === 'user'))
        ->willReturn($details);

    $customerAware->expects($this->once())
        ->method('syncCustomer')
        ->with($this->callback(fn($id) => $id->id() === 'user_1' && $id->type() === 'user'), $details)
        ->willReturn(\AlturaCode\Billing\Core\Provider\CustomerSyncResult::completed('cust_1'));

    $customerAware->expects($this->once())
        ->method('create')
        ->with($this->callback(fn($sub) => $sub->billable()->id() === 'user_1'))
        ->willReturnCallback(fn($sub) => BillingProviderResult::completed($sub));

    $this->subscriptions->expects($this->once())
        ->method('save');

    $result = $this->manager->createSubscription($draft);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('syncs product when provider is product-aware on creation', function () {
    $priceId = (string) new Ulid();
    $draft = new SubscriptionDraft(
        name: 'default',
        billableId: 'user_2',
        billableType: 'user',
        provider: 'stripe',
        priceId: $priceId
    );

    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'pro_plan',
        'name' => 'Pro Plan',
        'description' => 'Best plan',
        'prices' => [
            [
                'id' => $priceId,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1]
            ]
        ],
        'features' => []
    ]);

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn(null);

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn($product);

    $this->products->expects($this->once())
        ->method('all')
        ->willReturn([$product]);

    $productAware = $this->createMock(ProductAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($productAware);

    $productAware->expects($this->once())
        ->method('syncProduct')
        ->with($product, [])
        ->willReturn(ProductSyncResult::makeEmpty());

    $productAware->expects($this->once())
        ->method('create')
        ->willReturnCallback(fn($sub) => BillingProviderResult::completed($sub));

    $this->subscriptions->expects($this->once())
        ->method('save');

    $result = $this->manager->createSubscription($draft);

    expect($result)->toBeInstanceOf(BillingProviderResult::class);
});

it('throws ProductNotFoundException when product not found for price on creation', function () {
    $priceId = (string) new Ulid();
    $draft = new SubscriptionDraft(
        name: 'default',
        billableId: 'user_3',
        billableType: 'user',
        provider: 'stripe',
        priceId: $priceId
    );

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn(null);

    $productAware = $this->createMock(ProductAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($productAware);

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn(null);

    $this->manager->createSubscription($draft);
})->throws(ProductNotFoundException::class);

it('throws capability exception when pausing with non-pausable provider', function () {
    $subscription = hydrateSubscription('active');
    $subId = $subscription->id()->value();

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($this->callback(fn($arg) => $arg instanceof SubscriptionId && $arg->value() === $subId))
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->manager->pauseSubscription($subId);
})->throws(BillingProviderMissingCapabilityException::class);

it('throws capability exception when resuming with non-pausable provider', function () {
    $subscription = hydrateSubscription('paused');
    $subId = $subscription->id()->value();

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($this->callback(fn($arg) => $arg instanceof SubscriptionId && $arg->value() === $subId))
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->manager->resumeSubscription($subId);
})->throws(BillingProviderMissingCapabilityException::class);

it('syncs all products with product-aware provider', function () {
    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'std_plan',
        'name' => 'Std Plan',
        'description' => 'Std',
        'prices' => [],
        'features' => []
    ]);

    $this->products->expects($this->once())
        ->method('all')
        ->willReturn([$product]);

    $productAware = $this->createMock(ProductAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($productAware);

    $productAware->expects($this->once())
        ->method('syncProducts')
        ->with([$product], [])
        ->willReturn(ProductSyncResult::makeEmpty());

    $result = $this->manager->syncAllProducts('stripe');

    expect($result)->toBeInstanceOf(ProductSyncResult::class);
});

it('throws capability exception when syncing all products with non-product-aware provider', function () {
    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->manager->syncAllProducts('stripe');
})->throws(BillingProviderMissingCapabilityException::class);

it('syncs single product by price id with product-aware provider', function () {
    $priceId = (string) new Ulid();
    $product = Product::hydrate([
        'id' => (string)new Ulid(),
        'kind' => 'plan',
        'slug' => 'std_plan',
        'name' => 'Std Plan',
        'description' => 'Std',
        'prices' => [
            [
                'id' => $priceId,
                'price' => ['amount' => 1000, 'currency' => 'usd'],
                'interval' => ['type' => 'month', 'count' => 1]
            ]
        ],
        'features' => []
    ]);

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn($product);

    $productAware = $this->createMock(ProductAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($productAware);

    $productAware->expects($this->once())
        ->method('syncProduct')
        ->with($product, [])
        ->willReturn(ProductSyncResult::makeEmpty());

    $result = $this->manager->syncProductByPriceId('stripe', $priceId);

    expect($result)->toBeInstanceOf(ProductSyncResult::class);
});

it('throws ProductNotFoundException when syncing product by unknown price id', function () {
    $priceId = (string) new Ulid();

    $this->products->expects($this->once())
        ->method('findByPriceId')
        ->willReturn(null);

    $productAware = $this->createMock(ProductAwareBillingProvider::class);

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($productAware);

    $this->manager->syncProductByPriceId('stripe', $priceId);
})->throws(ProductNotFoundException::class);

it('throws capability exception when syncing product by price with non-product-aware provider', function () {
    $priceId = (string) new Ulid();

    $this->providerRegistry->expects($this->once())
        ->method('get')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->manager->syncProductByPriceId('stripe', $priceId);
})->throws(BillingProviderMissingCapabilityException::class);
