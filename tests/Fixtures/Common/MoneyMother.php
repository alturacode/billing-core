<?php

declare(strict_types=1);

namespace Tests\Fixtures\Common;

use AlturaCode\Billing\Core\Common\Money;

final class MoneyMother
{
    public static function create(int $amount = 1000, string $currency = 'usd'): Money
    {
        return Money::hydrate([
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }
}
