<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Common\DateRange;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId;
use Tests\Fixtures\Common\FeatureKeyMother;
use Tests\Fixtures\Common\FeatureValueMother;

final class SubscriptionItemEntitlementMother
{
    public static function create(
        ?SubscriptionItemEntitlementId $id = null,
        ?FeatureKey                    $key = null,
        ?FeatureValue                  $value = null,
        ?DateRange                    $effectiveWindow = null
    ): SubscriptionItemEntitlement {
        return SubscriptionItemEntitlement::create(
            $id ?? SubscriptionItemEntitlementIdMother::random(),
            $key ?? FeatureKeyMother::create(),
            $value ?? FeatureValueMother::flag(true),
            $effectiveWindow
        );
    }
}
