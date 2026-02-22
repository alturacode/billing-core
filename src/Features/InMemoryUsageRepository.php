<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;

/**
 * In-memory implementation of UsageRepository for testing.
 *
 * This implementation stores usage data in memory and is suitable for
 * single-threaded test scenarios. For production use, implement a
 * database-backed repository with proper locking mechanisms.
 */
final class InMemoryUsageRepository implements UsageRepository
{
    /**
     * @var array<string, int> Storage keyed by composite key
     */
    private array $usage = [];

    public function getUsedAmount(
        SubscriptionId $subscriptionId,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int {
        $key = $this->makeKey($subscriptionId, $featureKey, $window);
        return $this->usage[$key] ?? 0;
    }

    public function tryConsume(
        SubscriptionId $subscriptionId,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount,
        int $limit
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $key = $this->makeKey($subscriptionId, $featureKey, $window);
        $current = $this->usage[$key] ?? 0;

        // Check if adding this amount would exceed the limit
        if ($current + $amount > $limit) {
            return false;
        }

        // Atomically increment (in real implementations, use DB transactions or locks)
        $this->usage[$key] = $current + $amount;

        return true;
    }

    /**
     * Create a unique key for storing usage data.
     */
    private function makeKey(
        SubscriptionId $subscriptionId,
        FeatureKey $featureKey,
        UsageWindow $window
    ): string {
        return sprintf(
            '%s:%s:%s:%s',
            $subscriptionId->value(),
            $featureKey->value(),
            $window->startsAt()->format('Y-m-d\TH:i:s\Z'),
            $window->endsAt()->format('Y-m-d\TH:i:s\Z')
        );
    }

    /**
     * Clear all usage data (useful for tests).
     */
    public function clear(): void
    {
        $this->usage = [];
    }
}
