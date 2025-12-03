<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Money;
use AlturaCode\Billing\Core\ProductPriceId;
use AlturaCode\Billing\Core\SubscriptionItemId;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final readonly class SubscriptionItem
{
    public function __construct(
        private SubscriptionItemId $id,
        private ProductPriceId     $priceId,
        private int                $quantity,
        private Money              $price,
        private ?DateTimeImmutable $currentPeriodStartsAt = null,
        private ?DateTimeImmutable $currentPeriodEndsAt = null,
    )
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->assertValidPeriod();
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
            $this->id,
            $this->priceId,
            $quantity,
            $this->price,
            $this->currentPeriodStartsAt,
            $this->currentPeriodEndsAt
        );
    }

    public function withPeriodDates(DateTimeImmutable $currentPeriodStartsAt, DateTimeImmutable $currentPeriodEndsAt): self
    {
        return new self(
            $this->id,
            $this->priceId,
            $this->quantity,
            $this->price,
            $currentPeriodStartsAt,
            $currentPeriodEndsAt
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