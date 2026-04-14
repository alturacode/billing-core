<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;

interface UsageLedger
{
    /**
     * Record a raw usage event.
     *
     * Implementations should treat the event id as idempotent.
     *
     * @return bool True when the event was appended, false when it was already recorded.
     */
    public function record(UsageEvent $event): bool;

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
}
