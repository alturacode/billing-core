<?php

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Common\Money;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;

/**
 * Helpers
 */
function makeSubscription(?DateTimeImmutable $trialEndsAt = null): Subscription {
    return Subscription::create(
        id: SubscriptionId::generate(),
        name: SubscriptionName::fromString('main'),
        billable: BillableIdentity::fromString('user', 1),
        provider: SubscriptionProvider::fromString('stripe'),
        trialEndsAt: $trialEndsAt,
    );
}

function makeMoney(int $amount = 1000, string $currency = 'usd'): Money {
    return Money::hydrate(['amount' => $amount, 'currency' => $currency]);
}

function makeItem(string $currency = 'usd', ?SubscriptionItemId $id = null, int $quantity = 1): SubscriptionItem {
    return SubscriptionItem::create(
        id: $id ?? SubscriptionItemId::generate(),
        priceId: ProductPriceId::generate(),
        quantity: $quantity,
        price: makeMoney(1000, $currency),
        interval: ProductPriceInterval::monthly()
    );
}

it('creates an incomplete subscription without items', function () {
    $subscription = makeSubscription();

    expect($subscription->status())->toBe(SubscriptionStatus::Incomplete)
        ->and($subscription->isIncomplete())->toBeTrue()
        ->and($subscription->items())->toBeArray()->toHaveCount(0)
        ->and($subscription->entitlements())->toBeArray()->toHaveCount(0)
        ->and($subscription->cancelAtPeriodEnd())->toBeFalse()
        ->and($subscription->trialEndsAt())->toBeNull()
        ->and($subscription->canceledAt())->toBeNull();
});

it('adds a primary item and retrieves primary and addon items', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd');
    $addon = makeItem('usd');

    $subscription = $subscription->withPrimaryItem($primary);
    // Add addon by replacing items with both current primary and the addon
    $subscription = $subscription->withItems($subscription->primaryItem(), $addon);

    expect($subscription->items())->toHaveCount(2)
        ->and($subscription->primaryItem())->toBe($primary)
        ->and($subscription->addonItems())->toHaveCount(1)
        ->and($subscription->addonItems()[0])->toBe($addon)
        ->and($subscription->hasItem($primary->id()))->toBeTrue();
});

it('changes the primary item when provided id exists', function () {
    $subscription = makeSubscription();
    $one = makeItem('usd');
    $two = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($one)->withItems($one, $two);

    $subscription = $subscription->changePrimaryItem($two->id());

    expect($subscription->primaryItem())->toBe($two);
});

it('adds entitlements', function () {
    $entitlement = SubscriptionItemEntitlement::create(
        id: SubscriptionItemEntitlementId::generate(),
        key: FeatureKey::fromString('feature'),
        value: FeatureValue::flagOn(),
    );

    $item = makeItem();
    $subscription = makeSubscription()->withPrimaryItem($item);
    $subscription = $subscription->addEntitlementToItem($item, $entitlement);
    expect($subscription->entitlements())->toHaveCount(1);
});

it('throws when setting primary item to a non-existing item', function () {
    $subscription = makeSubscription();
    $one = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($one);

    $unknownId = SubscriptionItemId::generate();
    $subscription->changePrimaryItem($unknownId);
})->throws(DomainException::class);

it('throws when trying to get primary item when none is set', function () {
    $subscription = makeSubscription();
    $subscription->primaryItem();
})->throws(DomainException::class);

it('changes item quantity for an existing item', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd', quantity: 1);
    $addon = makeItem('usd', quantity: 2);
    $subscription = $subscription->withPrimaryItem($primary)->withItems($primary, $addon);

    $subscription = $subscription->changeItemQuantity($addon->id(), 5);

    $changedAddon = array_values(array_filter($subscription->items(), fn($i) => $i->id()->equals($addon->id())))[0];
    expect($changedAddon->quantity())->toBe(5)
        ->and($subscription->primaryItem()->quantity())->toBe(1);
});

it('throws when changing quantity for a non-existing item', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($primary);

    $subscription->changeItemQuantity(SubscriptionItemId::generate(), 3);
})->throws(DomainException::class);

it('sets item period dates for an existing item', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($primary);

    $start = new DateTimeImmutable('2025-01-01 00:00:00');
    $end = new DateTimeImmutable('2025-02-01 00:00:00');
    $subscription = $subscription->setItemPeriod($primary->id(), $start, $end);

    expect($subscription->primaryItem()->currentPeriodStartsAt())->toEqual($start)
        ->and($subscription->primaryItem()->currentPeriodEndsAt())->toEqual($end);
});

it('throws when setting period dates for a non-existing item', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($primary);

    $subscription->setItemPeriod(SubscriptionItemId::generate(), new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-02-01'));
})->throws(DomainException::class);

it('cannot activate without items', function () {
    $subscription = makeSubscription();
    $subscription->activate();
})->throws(DomainException::class);

it('cannot activate without a primary item', function () {
    $subscription = makeSubscription();
    $one = makeItem('usd');
    $subscription = $subscription->withItems($one);

    $subscription->activate();
})->throws(DomainException::class);

it('lifecycle: activate, pause, resume and cancel at period end', function () {
    $subscription = makeSubscription();
    $primary = makeItem('usd');
    $subscription = $subscription->withPrimaryItem($primary);

    $subscription = $subscription->activate();
    expect($subscription->isActive())->toBeTrue();

    $subscription = $subscription->pause();
    expect($subscription->isPaused())->toBeTrue();

    $subscription = $subscription->resume();
    expect($subscription->isActive())->toBeTrue();

    // Cancel at period end keeps status but sets flag
    $subscription = $subscription->cancel();
    expect($subscription->cancelAtPeriodEnd())->toBeTrue()
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->canceledAt())->toBeNull();
});

it('cancel immediately sets status to canceled and timestamp', function () {
    $subscription = makeSubscription()->withPrimaryItem(makeItem('usd'))->activate();

    $subscription = $subscription->cancel(false);
    expect($subscription->isCanceled())->toBeTrue()
        ->and($subscription->canceledAt())->not()->toBeNull();
});

it('cannot pause a canceled subscription and cannot resume an active or canceled subscription', function () {
    $subscription = makeSubscription()->withPrimaryItem(makeItem('usd'))->activate();
    // Resume active should throw
    expect(fn() => $subscription->resume())->toThrow(DomainException::class);

    // Cancel immediately
    $subscription = $subscription->cancel(false);
    // Pause canceled should throw
    expect(fn() => $subscription->pause())->toThrow(DomainException::class);
    // Resume canceled should throw
    expect(fn() => $subscription->resume())->toThrow(DomainException::class);
});

it('determines trial status based on provided time', function () {
    $trialEndsAt = new DateTimeImmutable('2025-01-10 00:00:00');
    $subscription = makeSubscription($trialEndsAt);

    expect($subscription->isInTrial(new DateTimeImmutable('2025-01-01 00:00:00')))->toBeTrue()
        ->and($subscription->isInTrial(new DateTimeImmutable('2025-01-10 00:00:00')))->toBeFalse()
        ->and($subscription->isInTrial(new DateTimeImmutable('2025-02-01 00:00:00')))->toBeFalse();
});

it('does not allow mixed currencies across items', function () {
    $subscription = makeSubscription();
    $usdItem = makeItem('usd');
    $eurItem = makeItem('eur');

    // Replacing items with different currencies should fail
    $subscription->withItems($usdItem, $eurItem);
})->throws(DomainException::class);

it('does not allow duplicate item ids', function () {
    $subscription = makeSubscription();
    $id = SubscriptionItemId::generate();
    $itemA = makeItem('usd', id: $id);
    $itemB = makeItem('usd', id: $id); // duplicate id

    $subscription->withItems($itemA, $itemB);
})->throws(DomainException::class);

