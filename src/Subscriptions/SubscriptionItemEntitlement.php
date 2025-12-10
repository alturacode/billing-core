<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use DateTimeImmutable;
use AlturaCode\Billing\Core\Common\DateRange;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;

final readonly class SubscriptionItemEntitlement
{
    private function __construct(
        private SubscriptionItemEntitlementId $id,
        private FeatureKey                    $key,
        private FeatureValue                  $value,
        private ?DateRange                    $effectiveWindow
    )
    {
    }

    public static function hydrate(mixed $data): self
    {
        return new self(...array_values($data));
    }

    public static function create(SubscriptionItemEntitlementId $id, FeatureKey $key, FeatureValue $value, ?DateRange $effectiveWindow = null): self
    {
        return new self($id, $key, $value, $effectiveWindow);
    }

    public function id(): SubscriptionItemEntitlementId
    {
        return $this->id;
    }

    public function key(): FeatureKey
    {
        return $this->key;
    }

    public function value(): FeatureValue
    {
        return $this->value;
    }

    public function effectiveWindow(): ?DateRange
    {
        return $this->effectiveWindow;
    }

    public function isActiveAt(DateTimeImmutable $date): bool
    {
        return $this->effectiveWindow === null || $this->effectiveWindow->isInRange($date);
    }
}