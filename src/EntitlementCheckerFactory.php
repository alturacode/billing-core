<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Subscriptions\Subscription;
use DateTimeImmutable;

final readonly class EntitlementCheckerFactory
{
    public function __construct(
        private EntitlementResolver $entitlementResolver
    )
    {
    }

    public function for(Subscription $subscription, DateTimeImmutable $at): EntitlementChecker
    {
        return new EntitlementChecker($this->entitlementResolver->resolve($subscription->entitlements(), $at));
    }
}