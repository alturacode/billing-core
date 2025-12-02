<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

final readonly class Money
{
    public function __construct(
        private int      $amount,
        private Currency $currency
    )
    {
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }
}