<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;

final readonly class SubscriptionEntitlement
{
    private function __construct(
        private SubscriptionEntitlementId $id,
        private FeatureKey                $key,
        private FeatureValue              $value,
    )
    {
    }

    public static function hydrate(mixed $data): self
    {
        return new self(...array_values($data));
    }

    public static function create(SubscriptionEntitlementId $id, FeatureKey $key, FeatureValue $value): self
    {
        return new self($id, $key, $value);
    }

    public function id(): SubscriptionEntitlementId
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
}