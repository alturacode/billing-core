<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Common\Money;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use DateTimeImmutable;
use Tests\Fixtures\Common\MoneyMother;
use Tests\Fixtures\Products\ProductPriceIdMother;
use Tests\Fixtures\Products\ProductPriceIntervalMother;

final class SubscriptionItemMother
{
    public static function create(
        ?SubscriptionItemId   $id = null,
        ?ProductPriceId       $priceId = null,
        int                   $quantity = 1,
        ?Money                $price = null,
        ?ProductPriceInterval $interval = null,
        array                 $entitlements = [],
        ?DateTimeImmutable    $currentPeriodStartsAt = null,
        ?DateTimeImmutable    $currentPeriodEndsAt = null,
    ): SubscriptionItem {
        return new SubscriptionItem(
            id: $id ?? SubscriptionItemIdMother::random(),
            priceId: $priceId ?? ProductPriceIdMother::random(),
            quantity: $quantity,
            price: $price ?? MoneyMother::create(),
            interval: $interval ?? ProductPriceIntervalMother::monthly(),
            entitlements: $entitlements,
            currentPeriodStartsAt: $currentPeriodStartsAt,
            currentPeriodEndsAt: $currentPeriodEndsAt,
        );
    }
}
