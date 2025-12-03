<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Money;
use AlturaCode\Billing\Core\ProductPriceId;
use AlturaCode\Billing\Core\SubscriptionItemId;
use InvalidArgumentException;

final readonly class SubscriptionItem
{
    public function __construct(
        private SubscriptionItemId $id,
        private ProductPriceId     $priceId,
        private int                $quantity,
        private Money              $price
    )
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
    }

    public function id(): SubscriptionItemId
    {
        return $this->id;
    }

    public function priceId(): ProductPriceId
    {
        return $this->priceId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->id, $this->priceId, $quantity, $this->price);
    }
}