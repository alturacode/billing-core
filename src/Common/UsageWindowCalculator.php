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
}
