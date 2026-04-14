<?php

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Common\UsagePolicy;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\EffectiveEntitlement;
use AlturaCode\Billing\Core\Features\InMemoryUsageLedger;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageEventId;
use AlturaCode\Billing\Core\UsageAwareEntitlementChecker;
use AlturaCode\Billing\Core\UsageAwareEntitlementCheckerFactory;
use Tests\Fixtures\Subscriptions\SubscriptionItemMother;
use Tests\Fixtures\Subscriptions\SubscriptionMother;

beforeEach(function () {
    $this->ledger = new InMemoryUsageLedger();
    $this->calculator = new UsageWindowCalculator();
    $this->billable = BillableIdentity::fromString('user', 123);
});

function makeChecker(array $entitlements, InMemoryUsageLedger $ledger, UsageWindowCalculator $calculator, BillableIdentity $billable): UsageAwareEntitlementChecker
{
    return new UsageAwareEntitlementChecker($entitlements, $ledger, $calculator, $billable);
}

it('returns false for non-existent features', function () {
    $checker = makeChecker([], $this->ledger, $this->calculator, $this->billable);

    expect($checker->canUse('non_existent'))->toBeFalse()
        ->and($checker->getUsedAmount('non_existent'))->toBe(0);
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);

    expect($checker->canUse('dark_mode'))->toBeTrue()
        ->and($checker->getUsedAmount('dark_mode'))->toBe(0);
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);

    expect($checker->canUse('dark_mode'))->toBeFalse();
});

it('allows unlimited limit features without blocking on usage', function () {
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);

    expect($checker->canUse('comments', 1000))->toBeTrue()
        ->and($checker->getUsedAmount('comments'))->toBe(0);
});

it('reads limit usage from the ledger', function () {
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);
    $ledgerWindowAt = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $this->billable,
        $featureKey,
        499,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($checker->getUsedAmount('comments', $ledgerWindowAt))->toBe(499)
        ->and($checker->canUse('comments', 1, $ledgerWindowAt))->toBeTrue()
        ->and($checker->canUse('comments', 2, $ledgerWindowAt))->toBeFalse();
});

it('respects month rollover when checking usage', function () {
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);

    $february = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $march = new DateTimeImmutable('2026-03-15 12:00:00', new DateTimeZone('UTC'));

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $this->billable,
        $featureKey,
        400,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $this->billable,
        $featureKey,
        500,
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($checker->getUsedAmount('comments', $february))->toBe(400)
        ->and($checker->canUse('comments', 100, $february))->toBeTrue()
        ->and($checker->getUsedAmount('comments', $march))->toBe(500)
        ->and($checker->canUse('comments', 1, $march))->toBeFalse();
});

it('supports perpetual windows', function () {
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

    $checker = makeChecker($entitlements, $this->ledger, $this->calculator, $this->billable);
    $january = new DateTimeImmutable('2026-01-15 12:00:00', new DateTimeZone('UTC'));
    $april = new DateTimeImmutable('2026-04-15 12:00:00', new DateTimeZone('UTC'));

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $this->billable,
        $featureKey,
        3,
        $january,
    ));

    expect($checker->getUsedAmount('websites', $april))->toBe(3)
        ->and($checker->canUse('websites', 1, $april))->toBeFalse();
});

it('can be created through the factory', function () {
    $featureKey = FeatureKey::fromString('comments');
    $policy = UsagePolicy::calendarMonth();

    $subscription = SubscriptionMother::create(
        billable: $this->billable,
        items: [
            SubscriptionItemMother::create(entitlements: [
                \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::create(
                    \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId::generate(),
                    $featureKey,
                    FeatureValue::limit(500, $policy),
                ),
            ]),
        ],
    );

    $factory = new UsageAwareEntitlementCheckerFactory(
        new \AlturaCode\Billing\Core\EntitlementResolver(),
        $this->ledger,
        $this->calculator
    );

    $checker = $factory->create($subscription, new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC')));

    expect($checker)->toBeInstanceOf(UsageAwareEntitlementChecker::class);
});
