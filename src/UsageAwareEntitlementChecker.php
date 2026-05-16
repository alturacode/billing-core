<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\Features\UsageMeter;
use DateTimeImmutable;

/**
 * Read-only usage-aware entitlement checker.
 */
final readonly class UsageAwareEntitlementChecker
{
    /**
     * @param array<string, EffectiveEntitlement> $effectiveEntitlements
     */
    public function __construct(
        private array $effectiveEntitlements,
        private UsageMeter $usageMeter,
        private UsageWindowCalculator $windowCalculator,
        private BillableIdentity $billable,
    ) {
    }

    public function canUse(string $keyName, int $newAmount = 1, ?DateTimeImmutable $at = null): bool
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return false;
        }

        $value = $effectiveEntitlement->value();

        return match ($value->kind()) {
            FeatureKind::Flag => $value->isOn(),
            FeatureKind::Limit => $this->canUseLimit($key, $value, $newAmount, $at),
        };
    }

    public function getUsedAmount(string $keyName, ?DateTimeImmutable $at = null): int
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return 0;
        }

        $value = $effectiveEntitlement->value();

        if ($value->kind() !== FeatureKind::Limit) {
            return 0;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return 0;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);

        return $this->usageMeter->getUsedAmount(
            $this->billable,
            $key,
            $window
        );
    }

    private function canUseLimit(
        FeatureKey $key,
        \AlturaCode\Billing\Core\Common\FeatureValue $value,
        int $newAmount,
        DateTimeImmutable $at
    ): bool {
        if ($value->isUnlimited()) {
            return true;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return false;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);
        $currentUsage = $this->usageMeter->getUsedAmount(
            $this->billable,
            $key,
            $window
        );

        return $value->staysWithinLimit($currentUsage + $newAmount);
    }
}
