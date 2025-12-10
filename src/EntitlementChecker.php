<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureKind;

final readonly class EntitlementChecker
{
    /**
     * @param array<string, EffectiveEntitlement> $effectiveEntitlements
     */
    public function __construct(
        private array $effectiveEntitlements,
    )
    {
    }

    public function canUse(string $keyName, int $newAmount = 1): bool
    {
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return false;
        }

        $usage = 0; // @todo once usage tracking is implemented, load usage profile for subscription and get from there
        $value = $effectiveEntitlement->value();

        return match ($value->kind()) {
            FeatureKind::Flag => $value->isOn(),
            FeatureKind::Limit => $value->isUnlimited() || $value->staysWithinLimit($usage + $newAmount),
        };
    }
}