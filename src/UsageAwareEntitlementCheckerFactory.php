<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\Features\UsageMeter;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use DateTimeImmutable;

final readonly class UsageAwareEntitlementCheckerFactory
{
    public function __construct(
        private EntitlementResolver $entitlementResolver,
        private UsageMeter $usageMeter,
        private UsageWindowCalculator $windowCalculator
    ) {
    }

    public function create(Subscription $subscription, DateTimeImmutable $at): UsageAwareEntitlementChecker
    {
        return new UsageAwareEntitlementChecker(
            $this->entitlementResolver->resolve($subscription->entitlements(), $at),
            $this->usageMeter,
            $this->windowCalculator,
            $subscription->billable()
        );
    }
}
