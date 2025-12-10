<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use DateTimeImmutable;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;

final readonly class EntitlementResolver
{
    /**
     * @param array<SubscriptionItemEntitlement> $grants
     * @return array<string, EffectiveEntitlement>
     */
    public function resolve(array $grants, DateTimeImmutable $at): array
    {
        $effective = [];

        foreach ($grants as $grant) {
            if (!$this->isActive($grant, $at)) {
                continue;
            }

            $key = $grant->key()->value();

            if (!isset($effective[$key])) {
                $effective[$key] = EffectiveEntitlement::fromGrant($grant);
                continue;
            }

            $effective[$key] = $effective[$key]->combinedWithGrant($grant);
        }

        return $effective;
    }

    private function isActive(SubscriptionItemEntitlement $grant, DateTimeImmutable $at): bool
    {
        return $grant->isActiveAt($at);
    }
}