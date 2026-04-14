<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;

final class InMemoryUsageLedger implements UsageLedger
{
    /**
     * @var array<string, UsageEvent>
     */
    private array $events = [];

    public function record(UsageEvent $event): bool
    {
        $key = $event->id()->value();

        if (isset($this->events[$key])) {
            return false;
        }

        $this->events[$key] = $event;

        return true;
    }

    public function getUsedAmount(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int {
        $used = 0;

        foreach ($this->events as $event) {
            if (!$this->matchesBillable($event, $billable)) {
                continue;
            }

            if (!$event->featureKey()->equals($featureKey)) {
                continue;
            }

            if (!$this->isWithinWindow($event, $window)) {
                continue;
            }

            $used += $event->amount();
        }

        return $used;
    }

    private function matchesBillable(UsageEvent $event, BillableIdentity $billable): bool
    {
        return $event->billable()->type() === $billable->type()
            && $event->billable()->id() === $billable->id();
    }

    private function isWithinWindow(UsageEvent $event, UsageWindow $window): bool
    {
        return $event->recordedAt() >= $window->startsAt()
            && $event->recordedAt() < $window->endsAt();
    }
}
