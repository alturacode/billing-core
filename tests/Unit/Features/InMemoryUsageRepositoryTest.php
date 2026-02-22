<?php

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsagePolicy;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\Features\InMemoryUsageRepository;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;

beforeEach(function () {
    $this->repository = new InMemoryUsageRepository();
    $this->calculator = new UsageWindowCalculator();
    $this->subscriptionId = SubscriptionId::generate();
    $this->featureKey = FeatureKey::fromString('comments');
    $this->policy = UsagePolicy::calendarMonth();
});

it('returns zero for new usage', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);

    expect($used)->toBe(0);
});

it('successfully consumes within limit', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    // First consumption
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 1, $limit);
    expect($result)->toBeTrue();

    // Verify usage incremented
    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);
    expect($used)->toBe(1);

    // Second consumption
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 10, $limit);
    expect($result)->toBeTrue();

    // Verify usage incremented again
    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);
    expect($used)->toBe(11);
});

it('prevents exceeding limit', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    // Consume up to the limit
    $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 499, $limit);
    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);
    expect($used)->toBe(499);

    // Try to consume more than remaining
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 2, $limit);
    expect($result)->toBeFalse();

    // Usage should remain unchanged
    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);
    expect($used)->toBe(499);
});

it('allows consumption exactly at limit', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    // Consume exactly to the limit
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 500, $limit);
    expect($result)->toBeTrue();

    $used = $this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window);
    expect($used)->toBe(500);

    // Next consumption should fail
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 1, $limit);
    expect($result)->toBeFalse();
});

it('isolates usage by subscription', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    $subscription1 = SubscriptionId::generate();
    $subscription2 = SubscriptionId::generate();

    // Consume for subscription 1
    $this->repository->tryConsume($subscription1, $this->featureKey, $window, 100, $limit);

    // Consume for subscription 2
    $this->repository->tryConsume($subscription2, $this->featureKey, $window, 200, $limit);

    // Each subscription should have its own usage
    expect($this->repository->getUsedAmount($subscription1, $this->featureKey, $window))->toBe(100)
        ->and($this->repository->getUsedAmount($subscription2, $this->featureKey, $window))->toBe(200);
});

it('isolates usage by feature key', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    $feature1 = FeatureKey::fromString('comments');
    $feature2 = FeatureKey::fromString('uploads');

    // Consume for feature 1
    $this->repository->tryConsume($this->subscriptionId, $feature1, $window, 100, $limit);

    // Consume for feature 2
    $this->repository->tryConsume($this->subscriptionId, $feature2, $window, 200, $limit);

    // Each feature should have its own usage
    expect($this->repository->getUsedAmount($this->subscriptionId, $feature1, $window))->toBe(100)
        ->and($this->repository->getUsedAmount($this->subscriptionId, $feature2, $window))->toBe(200);
});

it('isolates usage by window (different months)', function () {
    $policy = UsagePolicy::calendarMonth();
    $limit = 500;

    // February window
    $feb = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $febWindow = $this->calculator->forPolicyAt($policy, $feb);

    // March window
    $mar = new DateTimeImmutable('2026-03-15 12:00:00', new DateTimeZone('UTC'));
    $marWindow = $this->calculator->forPolicyAt($policy, $mar);

    // Consume in February
    $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $febWindow, 300, $limit);

    // Consume in March
    $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $marWindow, 400, $limit);

    // Each window should have independent usage
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $febWindow))->toBe(300)
        ->and($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $marWindow))->toBe(400);
});

it('rejects zero or negative amounts', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    // Zero amount
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 0, $limit);
    expect($result)->toBeFalse();

    // Negative amount
    $result = $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, -1, $limit);
    expect($result)->toBeFalse();
});

it('clears all usage data', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);
    $limit = 500;

    // Add some usage
    $this->repository->tryConsume($this->subscriptionId, $this->featureKey, $window, 100, $limit);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(100);

    // Clear
    $this->repository->clear();

    // Usage should be zero
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(0);
});

it('sets used amount directly', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    // Set to 50
    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, 50);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(50);

    // Set to 100
    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, 100);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(100);

    // Set back to 0
    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, 0);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(0);
});

it('throws when setting negative amount', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, -1);
})->throws(InvalidArgumentException::class);

it('increments usage by amount', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    // Start at 0
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(0);

    // Increment by 1
    $this->repository->incrementUsage($this->subscriptionId, $this->featureKey, $window, 1);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(1);

    // Increment by 5
    $this->repository->incrementUsage($this->subscriptionId, $this->featureKey, $window, 5);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(6);

    // Increment by 10
    $this->repository->incrementUsage($this->subscriptionId, $this->featureKey, $window, 10);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(16);
});

it('throws when incrementing by zero or negative', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    $this->repository->incrementUsage($this->subscriptionId, $this->featureKey, $window, 0);
})->throws(InvalidArgumentException::class);

it('decrements usage by amount', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    // Set to 10
    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, 10);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(10);

    // Decrement by 3
    $this->repository->decrementUsage($this->subscriptionId, $this->featureKey, $window, 3);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(7);

    // Decrement by 5
    $this->repository->decrementUsage($this->subscriptionId, $this->featureKey, $window, 5);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(2);

    // Decrement by 2
    $this->repository->decrementUsage($this->subscriptionId, $this->featureKey, $window, 2);
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(0);
});

it('does not go negative when decrementing', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    // Set to 5
    $this->repository->setUsedAmount($this->subscriptionId, $this->featureKey, $window, 5);

    // Try to decrement by 10 (more than current)
    $this->repository->decrementUsage($this->subscriptionId, $this->featureKey, $window, 10);

    // Should be 0, not negative
    expect($this->repository->getUsedAmount($this->subscriptionId, $this->featureKey, $window))->toBe(0);
});

it('throws when decrementing by zero or negative', function () {
    $at = new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'));
    $window = $this->calculator->forPolicyAt($this->policy, $at);

    $this->repository->decrementUsage($this->subscriptionId, $this->featureKey, $window, 0);
})->throws(InvalidArgumentException::class);
