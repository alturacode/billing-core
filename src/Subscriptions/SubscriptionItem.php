<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Common\Money;
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
        private array                $entitlements,
        private ?DateTimeImmutable   $currentPeriodStartsAt = null,
        private ?DateTimeImmutable   $currentPeriodEndsAt = null,
    )
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->assertValidPeriod();
        $this->assertEntitlementsAreNotRepeated();
    }

    public static function hydrate(array $data): self
    {
        return new self(
            id: SubscriptionItemId::fromString($data['id']),
            priceId: ProductPriceId::fromString($data['price_id']),
            quantity: $data['quantity'],
            price: Money::hydrate($data['price']),
            interval: ProductPriceInterval::hydrate($data['interval']),
            entitlements: array_map(fn(array $entitlement) => SubscriptionItemEntitlement::hydrate($entitlement), $data['entitlements'] ?? []),
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
        ProductPriceInterval $interval,
        array                $entitlements = [],
    ): self
    {
        return new self(
            id: $id,
            priceId: $priceId,
            quantity: $quantity,
            price: $price,
            interval: $interval,
            entitlements: $entitlements
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

    /**
     * @return SubscriptionItemEntitlement[]
     */
    public function entitlements(): array
    {
        return $this->entitlements;
    }

    public function withQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            priceId: $this->priceId,
            quantity: $quantity,
            price: $this->price,
            interval: $this->interval,
            entitlements: $this->entitlements,
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
            entitlements: $this->entitlements,
            currentPeriodStartsAt: $currentPeriodStartsAt,
            currentPeriodEndsAt: $currentPeriodEndsAt
        );
    }

    public function withEntitlements(SubscriptionItemEntitlement ...$entitlements): self
    {
        return new self(
            id: $this->id,
            priceId: $this->priceId,
            quantity: $this->quantity,
            price: $this->price,
            interval: $this->interval,
            entitlements: $entitlements,
            currentPeriodStartsAt: $this->currentPeriodStartsAt,
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

    private function assertEntitlementsAreNotRepeated(): void
    {
        $entitlements = array_map(fn(SubscriptionItemEntitlement $entitlement) => $entitlement->key(), $this->entitlements());
        if (count(array_unique($entitlements)) !== count($entitlements)) {
            throw new DomainException('Subscription entitlements must be unique.');
        }
    }
}