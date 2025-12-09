<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Money;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final readonly class SubscriptionItem
{
    public function __construct(
        private SubscriptionItemId   $id,
        private ProductPriceId       $priceId,
        private int                  $quantity,
        private Money                $price,
        private ProductPriceInterval $interval,
        private ?DateTimeImmutable   $currentPeriodStartsAt = null,
        private ?DateTimeImmutable   $currentPeriodEndsAt = null,
    )
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->assertValidPeriod();
    }

    public static function hydrate(array $data): self
    {
        return new self(
            id: SubscriptionItemId::fromString($data['id']),
            priceId: ProductPriceId::fromString($data['price_id']),
            quantity: $data['quantity'],
            price: Money::hydrate($data['price']),
            interval: ProductPriceInterval::hydrate($data['interval']),
            currentPeriodStartsAt: isset($data['current_period_starts_at'])
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['current_period_starts_at'])
                : null,
            currentPeriodEndsAt: isset($data['current_period_ends_at'])
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['current_period_ends_at'])
                : null,
        );
    }

    public static function create(
        SubscriptionItemId   $id,
        ProductPriceId       $priceId,
        int                  $quantity,
        Money                $price,
        ProductPriceInterval $interval
    ): self
    {
        return new self(
            id: $id,
            priceId: $priceId,
            quantity: $quantity,
            price: $price,
            interval: $interval
        );
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

    public function interval(): ProductPriceInterval
    {
        return $this->interval;
    }

    public function currentPeriodStartsAt(): ?DateTimeImmutable
    {
        return $this->currentPeriodStartsAt;
    }

    public function currentPeriodEndsAt(): ?DateTimeImmutable
    {
        return $this->currentPeriodEndsAt;
    }

    public function withQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            priceId: $this->priceId,
            quantity: $quantity,
            price: $this->price,
            interval: $this->interval,
            currentPeriodStartsAt: $this->currentPeriodStartsAt,
            currentPeriodEndsAt: $this->currentPeriodEndsAt
        );
    }

    public function withPeriodDates(DateTimeImmutable $currentPeriodStartsAt, DateTimeImmutable $currentPeriodEndsAt): self
    {
        return new self(
            id: $this->id,
            priceId: $this->priceId,
            quantity: $this->quantity,
            price: $this->price,
            interval: $this->interval,
            currentPeriodStartsAt: $currentPeriodStartsAt,
            currentPeriodEndsAt: $currentPeriodEndsAt
        );
    }

    private function assertValidPeriod(): void
    {
        if ($this->currentPeriodStartsAt !== null && $this->currentPeriodEndsAt === null) {
            throw new DomainException('Current period end date must be set when start date is present.');
        }

        if ($this->currentPeriodStartsAt === null && $this->currentPeriodEndsAt !== null) {
            throw new DomainException('Current period start date must be set when end date is present.');
        }

        if ($this->currentPeriodStartsAt !== null && $this->currentPeriodEndsAt !== null && $this->currentPeriodStartsAt >= $this->currentPeriodEndsAt) {
            throw new DomainException('Current period end date must be after start date.');
        }
    }
}