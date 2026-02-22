<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;

/**
 * Repository interface for tracking feature usage.
 *
 * Implementations must provide atomic operations to ensure usage limits
 * are enforced correctly in concurrent scenarios.
 */
interface UsageRepository
{
    /**
     * Get the current used amount for a subscription's feature within a usage window.
     *
     * @param SubscriptionId $subscriptionId The subscription identifier
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window to query
     * @return int The amount currently used (0 if no usage recorded)
     */
    public function getUsedAmount(
        SubscriptionId $subscriptionId,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int;

    /**
     * Atomically attempt to consume a specific amount of a feature.
     *
     * This operation must be atomic: it checks if usage + amount <= limit,
     * and only increments usage if the limit is not exceeded.
     *
     * @param SubscriptionId $subscriptionId The subscription identifier
     * @param FeatureKey $featureKey The feature key
     * @param UsageWindow $window The usage window
     * @param int $amount The amount to consume (must be positive)
     * @param int $limit The maximum allowed usage
     * @return bool True if consumption succeeded, false if limit would be exceeded
     */
    public function tryConsume(
        SubscriptionId $subscriptionId,
        FeatureKey $featureKey,
        UsageWindow $window,
        int $amount,
        int $limit
    ): bool;
}
