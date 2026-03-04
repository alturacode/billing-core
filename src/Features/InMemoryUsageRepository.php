<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;

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
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int {
        $key = $this->makeKey($billable, $featureKey, $window);
        return $this->usage[$key] ?? 0;
    }

    public function tryConsume(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount,
        int $limit
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $key = $this->makeKey($billable, $featureKey, $window);
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
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window
    ): string {
        return sprintf(
            '%s:%s:%s:%s:%s',
            $billable->type(),
            (string) $billable->id(),
            $featureKey->value(),
            $window->startsAt()->format('Y-m-d\TH:i:s\Z'),
            $window->endsAt()->format('Y-m-d\TH:i:s\Z')
        );
    }

    public function setUsedAmount(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        $key = $this->makeKey($billable, $featureKey, $window);
        $this->usage[$key] = $amount;
    }

    public function incrementUsage(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        $key = $this->makeKey($billable, $featureKey, $window);
        $current = $this->usage[$key] ?? 0;
        $this->usage[$key] = $current + $amount;
    }

    public function decrementUsage(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        $key = $this->makeKey($billable, $featureKey, $window);
        $current = $this->usage[$key] ?? 0;
        $newValue = max(0, $current - $amount); // Don't go negative
        $this->usage[$key] = $newValue;
    }

    /**
     * Clear all usage data (useful for tests).
     */
    public function clear(): void
    {
        $this->usage = [];
    }
}
