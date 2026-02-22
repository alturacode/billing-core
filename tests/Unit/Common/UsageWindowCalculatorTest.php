<?php

use AlturaCode\Billing\Core\Common\UsagePolicy;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;

it('calculates calendar month window for mid-month date', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2026-02-15 15:30:00 UTC
    $at = new DateTimeImmutable('2026-02-15 15:30:00', new DateTimeZone('UTC'));
    $window = $calculator->forPolicyAt($policy, $at);

    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2026-02-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00');
});

it('calculates calendar month window for first day of month', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2026-03-01 00:00:00 UTC
    $at = new DateTimeImmutable('2026-03-01 00:00:00', new DateTimeZone('UTC'));
    $window = $calculator->forPolicyAt($policy, $at);

    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2026-04-01 00:00:00');
});

it('calculates calendar month window for last day of month', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2026-02-28 23:59:59 UTC (February in non-leap year)
    $at = new DateTimeImmutable('2026-02-28 23:59:59', new DateTimeZone('UTC'));
    $window = $calculator->forPolicyAt($policy, $at);

    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2026-02-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00');
});

it('handles February in leap year correctly', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2024-02-29 12:00:00 UTC (leap year)
    $at = new DateTimeImmutable('2024-02-29 12:00:00', new DateTimeZone('UTC'));
    $window = $calculator->forPolicyAt($policy, $at);

    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2024-02-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2024-03-01 00:00:00');
});

it('handles year boundaries correctly', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2025-12-31 23:59:59 UTC
    $at = new DateTimeImmutable('2025-12-31 23:59:59', new DateTimeZone('UTC'));
    $window = $calculator->forPolicyAt($policy, $at);

    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2025-12-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2026-01-01 00:00:00');
});

it('converts non-UTC times to UTC for window calculation', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Test with 2026-02-15 15:30:00 America/New_York (20:30:00 UTC)
    $at = new DateTimeImmutable('2026-02-15 15:30:00', new DateTimeZone('America/New_York'));
    $window = $calculator->forPolicyAt($policy, $at);

    // Should still be February in UTC
    expect($window->startsAt()->format('Y-m-d H:i:s'))->toBe('2026-02-01 00:00:00')
        ->and($window->endsAt()->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00')
        ->and($window->startsAt()->getTimezone()->getName())->toBe('UTC');
});

it('ensures consecutive months have non-overlapping windows', function () {
    $calculator = new UsageWindowCalculator();
    $policy = UsagePolicy::calendarMonth();

    // Last second of February
    $feb = new DateTimeImmutable('2026-02-28 23:59:59', new DateTimeZone('UTC'));
    $febWindow = $calculator->forPolicyAt($policy, $feb);

    // First second of March
    $mar = new DateTimeImmutable('2026-03-01 00:00:00', new DateTimeZone('UTC'));
    $marWindow = $calculator->forPolicyAt($policy, $mar);

    // February window ends exactly when March window starts
    expect($febWindow->endsAt()->format('Y-m-d H:i:s'))
        ->toBe($marWindow->startsAt()->format('Y-m-d H:i:s'))
        ->toBe('2026-03-01 00:00:00');
});
