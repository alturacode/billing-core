<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;

final readonly class EffectiveEntitlement
{
    private function __construct(
        private FeatureKey $key, private FeatureValue $value)
    {
    }

    public static function fromGrant(SubscriptionItemEntitlement $grant): self
    {
        return new self($grant->key(), $grant->value());
    }

    public function combinedWithGrant(SubscriptionItemEntitlement $grant): self
    {
        return new self(
            $this->key,
            $this->value->combine($grant->value())
        );
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