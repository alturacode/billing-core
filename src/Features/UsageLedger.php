<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

interface UsageLedger extends UsageMeter
{
    /**
     * Record a raw usage event.
     *
     * Implementations should treat the event id as idempotent.
     *
     * @return bool True when the event was appended, false when it was already recorded.
     */
    public function record(UsageEvent $event): bool;
}
