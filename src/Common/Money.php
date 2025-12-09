<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

final readonly class Money
{
    private function __construct(
        private int      $amount,
        private Currency $currency
    )
    {
    }

    public static function hydrate(array $data): self
    {
        return new self($data['amount'], Currency::fromString($data['currency']));
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