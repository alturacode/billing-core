<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;

final class SubscriptionNameMother
{
    public static function create(string $value = 'main'): SubscriptionName
    {
        return SubscriptionName::fromString($value);
    }
}
