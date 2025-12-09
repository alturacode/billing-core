<?php

use AlturaCode\Billing\Core\BillingManager;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\BillingProvider;
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\SubscriptionAlreadyExists;
use AlturaCode\Billing\Core\SubscriptionDraft;
use AlturaCode\Billing\Core\SubscriptionNotFoundException;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    $this->products = $this->createMock(ProductRepository::class);
    $this->subscriptions = $this->createMock(SubscriptionRepository::class);
    $this->providerRegistry = $this->createMock(BillingProviderRegistry::class);
    $this->billingProvider = $this->createMock(BillingProvider::class);

    $this->manager = new BillingManager(
        $this->products,
        $this->subscriptions,
        $this->providerRegistry
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
        priceId: $priceId,
        provider: 'stripe'
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
        ->method('findMultipleByPriceIds')
        ->willReturn([$product]);

    $this->providerRegistry->expects($this->once())
        ->method('subscriptionProviderFor')
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
        priceId: $priceId,
        provider: 'stripe'
    );

    $existingSubscription = hydrateSubscription('active');

    $this->subscriptions->expects($this->once())
        ->method('findForBillable')
        ->willReturn($existingSubscription);

    $this->manager->createSubscription($draft);
})->throws(SubscriptionAlreadyExists::class, 'Subscription for logical name "default" already exists');

it('cancels an existing subscription', function () {
    $subId = (string)new Ulid();
    $subscription = hydrateSubscription('active');

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->with($this->callback(fn($arg) => $arg instanceof SubscriptionId && $arg->value() === $subId))
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('subscriptionProviderFor')
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

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('subscriptionProviderFor')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->billingProvider->expects($this->once())
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

    $this->subscriptions->expects($this->once())
        ->method('find')
        ->willReturn($subscription);

    $this->providerRegistry->expects($this->once())
        ->method('subscriptionProviderFor')
        ->with('stripe')
        ->willReturn($this->billingProvider);

    $this->billingProvider->expects($this->once())
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
