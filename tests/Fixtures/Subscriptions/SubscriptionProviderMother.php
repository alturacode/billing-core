<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;

final class SubscriptionProviderMother
{
    public static function create(string $value = 'stripe'): SubscriptionProvider
    {
        return SubscriptionProvider::fromString($value);
    }
}
