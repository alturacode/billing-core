<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId;

final class SubscriptionItemEntitlementIdMother
{
    public static function create(string $value = '01J6H6J6H6J6H6J6H6J6H6J6H6'): SubscriptionItemEntitlementId
    {
        return SubscriptionItemEntitlementId::fromString($value);
    }

    public static function random(): SubscriptionItemEntitlementId
    {
        return SubscriptionItemEntitlementId::generate();
    }
}
