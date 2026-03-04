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

it('enforces perpetual limits with tryConsume', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(3, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Should be able to consume up to 3
    expect($checker->tryConsume('websites', 1))->toBeTrue()
        ->and($checker->getUsedAmount('websites'))->toBe(1);

    expect($checker->tryConsume('websites', 1))->toBeTrue()
        ->and($checker->getUsedAmount('websites'))->toBe(2);

    expect($checker->tryConsume('websites', 1))->toBeTrue()
        ->and($checker->getUsedAmount('websites'))->toBe(3);

    // 4th should fail
    expect($checker->tryConsume('websites', 1))->toBeFalse()
        ->and($checker->getUsedAmount('websites'))->toBe(3);
});

it('increments usage for perpetual limits', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(3, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Increment by 1 (creating first website)
    $checker->incrementUsage('websites', 1);
    expect($checker->getUsedAmount('websites'))->toBe(1);

    // Increment by 1 (creating second website)
    $checker->incrementUsage('websites', 1);
    expect($checker->getUsedAmount('websites'))->toBe(2);
});

it('decrements usage for perpetual limits', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(3, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Create 3 websites
    $checker->incrementUsage('websites', 1);
    $checker->incrementUsage('websites', 1);
    $checker->incrementUsage('websites', 1);
    expect($checker->getUsedAmount('websites'))->toBe(3);

    // Can't create more
    expect($checker->canUse('websites', 1))->toBeFalse();

    // Delete one website
    $checker->decrementUsage('websites', 1);
    expect($checker->getUsedAmount('websites'))->toBe(2);

    // Now can create another
    expect($checker->canUse('websites', 1))->toBeTrue();
    $checker->incrementUsage('websites', 1);
    expect($checker->getUsedAmount('websites'))->toBe(3);
});

it('sets usage amount directly for perpetual limits', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(3, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Set to 2 (after recounting)
    $checker->setUsedAmount('websites', 2);
    expect($checker->getUsedAmount('websites'))->toBe(2);

    // Can create one more
    expect($checker->canUse('websites', 1))->toBeTrue();

    // Set to 3
    $checker->setUsedAmount('websites', 3);
    expect($checker->canUse('websites', 1))->toBeFalse();
});

it('perpetual limits do not reset across different times', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(3, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Create 3 websites in January
    $january = new DateTimeImmutable('2026-01-15 12:00:00', new DateTimeZone('UTC'));
    $checker->incrementUsage('websites', 1, $january);
    $checker->incrementUsage('websites', 1, $january);
    $checker->incrementUsage('websites', 1, $january);

    // Check in February - usage should still be 3 (not reset)
    $february = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    expect($checker->getUsedAmount('websites', $february))->toBe(3);
    expect($checker->canUse('websites', 1, $february))->toBeFalse();

    // Check in March - usage should still be 3 (not reset)
    $march = new DateTimeImmutable('2026-03-15 12:00:00', new DateTimeZone('UTC'));
    expect($checker->getUsedAmount('websites', $march))->toBe(3);

    // Delete one in March
    $checker->decrementUsage('websites', 1, $march);

    // Check in April - usage should be 2 (reflects the deletion)
    $april = new DateTimeImmutable('2026-04-15 12:00:00', new DateTimeZone('UTC'));
    expect($checker->getUsedAmount('websites', $april))->toBe(2);
    expect($checker->canUse('websites', 1, $april))->toBeTrue();
});

it('combines perpetual limits with tryConsume and manual adjustments', function () {
    $featureKey = FeatureKey::fromString('websites');
    $policy = UsagePolicy::perpetual();

    $entitlements = [
        $featureKey->value() => EffectiveEntitlement::fromGrant(
            \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                $featureKey,
                FeatureValue::limit(5, $policy),
            )
        ),
    ];

    $checker = new UsageAwareEntitlementChecker(
        $entitlements,
        $this->repository,
        $this->calculator,
        $this->billable
    );

    // Use tryConsume to check and consume
    expect($checker->tryConsume('websites', 1))->toBeTrue();
    expect($checker->getUsedAmount('websites'))->toBe(1);

    // Use incrementUsage to add more
    $checker->incrementUsage('websites', 2);
    expect($checker->getUsedAmount('websites'))->toBe(3);

    // Try to consume more
    expect($checker->tryConsume('websites', 2))->toBeTrue();
    expect($checker->getUsedAmount('websites'))->toBe(5);

    // At limit
    expect($checker->canUse('websites', 1))->toBeFalse();

    // Decrement
    $checker->decrementUsage('websites', 2);
    expect($checker->getUsedAmount('websites'))->toBe(3);

    // Can use again
    expect($checker->canUse('websites', 2))->toBeTrue();
});
