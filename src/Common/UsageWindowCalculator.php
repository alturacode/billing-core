<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;

final class UsageWindowCalculator
{
    /**
     * Calculate the usage window for a given policy at a specific point in time.
     * All calculations are performed in UTC.
     *
     * @param UsagePolicy $policy The usage policy defining the period
     * @param DateTimeImmutable $at The point in time to calculate the window for
     * @return UsageWindow The calculated usage window
     */
    public function forPolicyAt(UsagePolicy $policy, DateTimeImmutable $at): UsageWindow
    {
        // Ensure we're working in UTC
        $atUtc = $at->setTimezone(new DateTimeZone('UTC'));

        return match ($policy->period()) {
            UsagePeriod::Month => $this->calculateMonthWindow($atUtc),
            UsagePeriod::Perpetual => $this->calculatePerpetualWindow(),
            default => throw new LogicException('Unsupported usage period: ' . $policy->period()->value),
        };
    }

    /**
     * Calculate a calendar month window.
     * Start: First day of the month at 00:00:00 UTC
     * End: First day of the next month at 00:00:00 UTC (exclusive)
     */
    private function calculateMonthWindow(DateTimeImmutable $at): UsageWindow
    {
        // Set to the first day of the month at 00:00:00
        $start = $at->setDate(
            (int)$at->format('Y'),
            (int)$at->format('m'),
            1
        )->setTime(0, 0, 0);

        // Add one month to get the start of the next month
        $end = $start->modify('+1 month');

        return UsageWindow::create($start, $end);
    }

    /**
     * Calculate a perpetual window that never expires.
     * This is used for limits that don't reset (e.g., "Up to 3 websites").
     * Start: Unix epoch (1970-01-01 00:00:00 UTC)
     * End: Far future (9999-12-31 23:59:59 UTC)
     */
    private function calculatePerpetualWindow(): UsageWindow
    {
        $start = new DateTimeImmutable('1970-01-01 00:00:00', new DateTimeZone('UTC'));
        $end = new DateTimeImmutable('9999-12-31 23:59:59', new DateTimeZone('UTC'));

        return UsageWindow::create($start, $end);
    }
}
