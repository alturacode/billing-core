<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;

final class SubscriptionItemIdMother
{
    public static function create(string $value = '01J6H6J6H6J6H6J6H6J6H6J6H6'): SubscriptionItemId
    {
        return SubscriptionItemId::fromString($value);
    }

    public static function random(): SubscriptionItemId
    {
        return SubscriptionItemId::generate();
    }
}
