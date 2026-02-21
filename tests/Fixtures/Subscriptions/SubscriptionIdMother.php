<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;

final class SubscriptionIdMother
{
    public static function create(string $value = '01J6H6J6H6J6H6J6H6J6H6J6H6'): SubscriptionId
    {
        return SubscriptionId::fromString($value);
    }

    public static function random(): SubscriptionId
    {
        return SubscriptionId::generate();
    }
}
