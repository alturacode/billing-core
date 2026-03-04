<?php

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Common\UsagePolicy;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\EffectiveEntitlement;
use AlturaCode\Billing\Core\Features\InMemoryUsageRepository;
use AlturaCode\Billing\Core\UsageAwareEntitlementChecker;

beforeEach(function () {
    $this->repository = new InMemoryUsageRepository();
    $this->calculator = new UsageWindowCalculator();
    $this->billable = BillableIdentity::fromString('user', 123);
});

it('returns false for non-existent features', function () {
    $entitlements = [];
    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    expect($checker->canUse('non_existent'))->toBeFalse()
        ->and($checker->tryConsume('non_existent'))->toBeFalse();
});

it('returns flag state for flag features', function () {
    $featureKey = FeatureKey::fromString('dark_mode');
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::flagOn(),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    expect($checker->canUse('dark_mode'))->toBeTrue()
        ->and($checker->tryConsume('dark_mode'))->toBeTrue();
});

it('returns false for off flag features', function () {
    $featureKey = FeatureKey::fromString('dark_mode');
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::flagOff(),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    expect($checker->canUse('dark_mode'))->toBeFalse()
        ->and($checker->tryConsume('dark_mode'))->toBeFalse();
});

it('allows unlimited limit features without tracking', function () {
    $featureKey = FeatureKey::fromString('comments');
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::hydrate(['kind' => 'limit', 'value' => 'unlimited', 'usage_policy' => ['period' => 'month']]),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Should always return true without tracking usage
    expect($checker->canUse('comments', 1000))->toBeTrue()
        ->and($checker->tryConsume('comments', 1000))->toBeTrue()
        ->and($this->repository->getUsedAmount(
            $this->billable,
            $featureKey,
            $this->calculator->forPolicyAt(UsagePolicy::calendarMonth(), new DateTimeImmutable())
        ))->toBe(0); // No usage tracked
});

it('successfully consumes within limit', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    expect($checker->tryConsume('comments', 1))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(1)
        ->and($checker->tryConsume('comments', 10))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(11);
});

it('prevents exceeding limit with tryConsume', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Consume up to near the limit
    $checker->tryConsume('comments', 499);

    // Try to exceed
    expect($checker->tryConsume('comments', 2))->toBeFalse()
        ->and($checker->getUsedAmount('comments'))->toBe(499); // Should not have incremented
});

it('allows consumption exactly at limit', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    expect($checker->tryConsume('comments', 500))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(500)
        ->and($checker->tryConsume('comments', 1))->toBeFalse();
});

it('resets usage in new month window', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Consume in February
    $february = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    expect($checker->tryConsume('comments', 400, $february))->toBeTrue()
        ->and($checker->getUsedAmount('comments', $february))->toBe(400);

    // Check March - should be fresh window
    $march = new DateTimeImmutable('2026-03-15 12:00:00', new DateTimeZone('UTC'));
    expect($checker->getUsedAmount('comments', $march))->toBe(0)
        ->and($checker->tryConsume('comments', 500, $march))->toBeTrue()
        ->and($checker->getUsedAmount('comments', $march))->toBe(500);

    // February usage should remain unchanged
    expect($checker->getUsedAmount('comments', $february))->toBe(400);
});

it('canUse performs non-atomic check', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();
    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Check without consuming
    expect($checker->canUse('comments', 1))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(0); // No usage tracked

    // Consume some
    $checker->tryConsume('comments', 499);

    // Check if we can use 1 more (should be true)
    expect($checker->canUse('comments', 1))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(499);

    // Check if we can use 2 more (should be false)
    expect($checker->canUse('comments', 2))->toBeFalse();
});

it('isolates usage by billable identity', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();

    $billable1 = BillableIdentity::fromString('user', 101);
    $billable2 = BillableIdentity::fromString('user', 202);

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker1 = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $billable1
    );

    $checker2 = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $billable2
    );

    // Consume for subscription 1
    $checker1->tryConsume('comments', 300);

    // Consume for subscription 2
    $checker2->tryConsume('comments', 400);

    // Each should have its own usage
    expect($checker1->getUsedAmount('comments'))->toBe(300)
        ->and($checker2->getUsedAmount('comments'))->toBe(400);
});

it('shares usage across checkers with the same billable identity', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();

    $billable = BillableIdentity::fromString('user', 303);

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(500, $policy),
            )
        ),
    ];

    $checker1 = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $billable
    );

    $checker2 = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $billable
    );

    $checker1->tryConsume('comments', 120);

    expect($checker2->getUsedAmount('comments'))->toBe(120);
});
