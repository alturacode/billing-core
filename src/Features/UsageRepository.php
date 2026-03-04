<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;

/**
 * Repository interface for tracking feature usage.
 *
 * Implementations must provide atomic operations to ensure usage limits
 * are enforced correctly in concurrent scenarios.
 */
interface UsageRepository
{
    /**
     * Get the current used amount for a billable's feature within a usage window.
     *
     * @param BillableIdentity $billable The billable identity
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window to query
     * @return int The amount currently used (0 if no usage recorded)
     */
    public function getUsedAmount(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int;

    /**
     * Atomically attempt to consume a specific amount of a feature.
     *
     * This operation must be atomic: it checks if usage + amount <= limit,
     * and only increments usage if the limit is not exceeded.
     *
     * @param BillableIdentity $billable The billable identity
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window
     * @param int $amount The amount to consume (must be positive)
     * @param int $limit The maximum allowed usage
     * @return bool True if consumption succeeded, false if limit would be exceeded
     */
    public function tryConsume(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount,
        int $limit
    ): bool;

    /**
     * Set the used amount directly for a billable's feature.
     *
     * This is useful for perpetual limits where you want to set an exact value
     * (e.g., after recounting resources).
     *
     * @param BillableIdentity $billable The billable identity
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window
     * @param int $amount The amount to set (must be non-negative)
     * @return void
     */
    public function setUsedAmount(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void;

    /**
     * Increment the used amount by a specific value.
     *
     * This is useful for perpetual limits when creating a resource
     * (e.g., creating a website increments the "websites" counter).
     *
     * @param BillableIdentity $billable The billable identity
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window
     * @param int $amount The amount to increment by (must be positive)
     * @return void
     */
    public function incrementUsage(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void;

    /**
     * Decrement the used amount by a specific value.
     *
     * This is useful for perpetual limits when deleting a resource
     * (e.g., deleting a website decrements the "websites" counter).
     *
     * @param BillableIdentity $billable The billable identity
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window
     * @param int $amount The amount to decrement by (must be positive)
     * @return void
     */
    public function decrementUsage(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount
    ): void;
}
