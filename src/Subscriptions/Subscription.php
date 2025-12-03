<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Money;
use AlturaCode\Billing\Core\ProductPriceId;
use AlturaCode\Billing\Core\SubscriptionCustomerId;
use AlturaCode\Billing\Core\SubscriptionId;
use AlturaCode\Billing\Core\SubscriptionItemId;
use DateTimeImmutable;
use DomainException;

final readonly class Subscription
{
    /**
     * @param SubscriptionId $id
     * @param SubscriptionCustomerId $customerId
     * @param SubscriptionProvider $provider
     * @param SubscriptionName $name
     * @param SubscriptionStatus $status
     * @param array<SubscriptionItem> $items
     * @param SubscriptionItemId $primaryItemId
     * @param DateTimeImmutable $createdAt
     * @param bool $cancelAtPeriodEnd
     * @param DateTimeImmutable|null $trialEndsAt
     * @param DateTimeImmutable|null $canceledAt
     */
    public function __construct(
        private SubscriptionId         $id,
        private SubscriptionCustomerId $customerId,
        private SubscriptionProvider   $provider,
        private SubscriptionName       $name,
        private SubscriptionStatus     $status,
        private array                  $items,
        private SubscriptionItemId     $primaryItemId,
        private DateTimeImmutable      $createdAt,
        private bool                   $cancelAtPeriodEnd = false,
        private ?DateTimeImmutable     $trialEndsAt = null,
        private ?DateTimeImmutable     $canceledAt = null,
    )
    {
        $this->assertAtLeastOneItem();
        $this->assertPrimaryItemRequired();
        $this->assertAllItemsHaveSameCurrency();
        $this->assertAllItemsHavePeriodDatesWhenActive();
        $this->assertCanceledMatchesStatus();
    }

    /**
     * @param SubscriptionName $name
     * @param SubscriptionCustomerId $customerId
     * @param ProductPriceId $productPriceId
     * @param Money $price
     * @param SubscriptionProvider $provider
     * @param int $quantity
     * @param DateTimeImmutable|null $trialEndsAt
     * @param array<array{priceId: ProductPriceId, quantity: int, price: Money}> $items
     * @return Subscription
     */
    public static function create(
        SubscriptionName       $name,
        SubscriptionCustomerId $customerId,
        ProductPriceId         $productPriceId,
        Money                  $price,
        SubscriptionProvider   $provider,
        int                    $quantity = 1,
        ?DateTimeImmutable     $trialEndsAt = null,
        array                  $items = [],
    ): Subscription
    {
        return new self(
            id: SubscriptionId::generate(),
            customerId: $customerId,
            provider: $provider,
            name: $name,
            status: SubscriptionStatus::Incomplete,
            items: [new SubscriptionItem(
                id: $primaryItemId = SubscriptionItemId::generate(),
                priceId: $productPriceId,
                quantity: $quantity,
                price: $price,
            ), ...array_map(fn($item) => new SubscriptionItem(
                id: SubscriptionItemId::generate(),
                priceId: $item['priceId'],
                quantity: $item['quantity'],
                price: $item['price'],
            ), $items)],
            primaryItemId: $primaryItemId,
            createdAt: new DateTimeImmutable(),
            trialEndsAt: $trialEndsAt
        );
    }

    public function id(): SubscriptionId
    {
        return $this->id;
    }

    public function customerId(): SubscriptionCustomerId
    {
        return $this->customerId;
    }

    public function provider(): SubscriptionProvider
    {
        return $this->provider;
    }

    public function name(): SubscriptionName
    {
        return $this->name;
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function cancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function trialEndsAt(): ?DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function canceledAt(): ?DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function primaryItem(): SubscriptionItem
    {
        foreach ($this->items as $item) {
            if ($item->id()->equals($this->primaryItemId)) {
                return $item;
            }
        }

        throw new DomainException('Primary item not found.');
    }

    /** @return SubscriptionItem[] */
    public function addonItems(): array
    {
        return array_values(array_filter(
            $this->items,
            fn(SubscriptionItem $item) => !$item->id()->equals($this->primaryItemId),
        ));
    }

    public function changePrimaryItem(SubscriptionItemId $newPrimaryId): Subscription
    {
        if (array_any($this->items, fn($item) => $item->id()->equals($newPrimaryId))) {
            return new self(
                $this->id,
                $this->customerId,
                $this->provider,
                $this->name,
                $this->status,
                $this->items,
                $newPrimaryId,
                $this->createdAt,
                $this->cancelAtPeriodEnd,
                $this->trialEndsAt,
                $this->canceledAt
            );
        }

        throw new DomainException('Cannot set primary item to an item that is not part of the subscription.');
    }

    public function changeItemQuantity(SubscriptionItemId $itemId, int $quantity): Subscription
    {
        if (array_any($this->items, fn($item) => $item->id()->equals($itemId))) {
            return new self(
                $this->id,
                $this->customerId,
                $this->provider,
                $this->name,
                $this->status,
                array_map(
                    fn(SubscriptionItem $item) => $item->id()->equals($itemId)
                        ? $item->withQuantity($quantity)
                        : $item,
                    $this->items
                ),
                $this->primaryItemId,
                $this->createdAt,
                $this->cancelAtPeriodEnd,
                $this->trialEndsAt,
                $this->canceledAt
            );
        }

        throw new DomainException('Cannot change quantity of item that is not part of the subscription.');
    }

    public function isInTrial(DateTimeImmutable $now): bool
    {
        return $this->trialEndsAt !== null && $this->trialEndsAt > $now;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function hasItem(SubscriptionItemId $itemId): bool
    {
        return array_any($this->items, fn(SubscriptionItem $item) => $item->id()->equals($itemId));
    }

    public function setItemPeriod(
        SubscriptionItemId $itemId,
        DateTimeImmutable  $currentPeriodStartsAt,
        DateTimeImmutable  $currentPeriodEndsAt
    ): Subscription
    {
        if (array_any($this->items, fn($item) => $item->id()->equals($itemId))) {
            return new self(
                $this->id,
                $this->customerId,
                $this->provider,
                $this->name,
                $this->status,
                array_map(fn(SubscriptionItem $item) => $item->id()->equals($itemId) ? $item->withPeriodDates($currentPeriodStartsAt, $currentPeriodEndsAt) : $item, $this->items),
                $this->primaryItemId,
                $this->createdAt,
                $this->cancelAtPeriodEnd,
                $this->trialEndsAt,
                $this->canceledAt
            );
        }

        throw new DomainException('Cannot set period dates of item that is not part of the subscription.');
    }

    public function activate(): Subscription
    {
        if ($this->status === SubscriptionStatus::Active) {
            return $this;
        }

        if ($this->status === SubscriptionStatus::Canceled) {
            throw new DomainException('Cannot activate a canceled subscription.');
        }

        return new self(
            id: $this->id,
            customerId: $this->customerId,
            provider: $this->provider,
            name: $this->name,
            status: SubscriptionStatus::Active,
            items: $this->items,
            primaryItemId: $this->primaryItemId,
            createdAt: $this->createdAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
        );
    }

    public function cancel(bool $atPeriodEnd = true): Subscription
    {
        if ($this->status === SubscriptionStatus::Canceled) {
            return $this;
        }

        return new self(
            id: $this->id,
            customerId: $this->customerId,
            provider: $this->provider,
            name: $this->name,
            status: $atPeriodEnd ? $this->status : SubscriptionStatus::Canceled,
            items: $this->items,
            primaryItemId: $this->primaryItemId,
            createdAt: $this->createdAt,
            cancelAtPeriodEnd: $atPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: $atPeriodEnd ? null : new DateTimeImmutable(),
        );
    }

    public function pause(): Subscription
    {
        if ($this->status === SubscriptionStatus::Canceled) {
            throw new DomainException('Cannot pause a canceled subscription.');
        }

        return new self(
            id: $this->id,
            customerId: $this->customerId,
            provider: $this->provider,
            name: $this->name,
            status: SubscriptionStatus::Paused,
            items: $this->items,
            primaryItemId: $this->primaryItemId,
            createdAt: $this->createdAt,
            cancelAtPeriodEnd: $this->cancelAtPeriodEnd,
            trialEndsAt: $this->trialEndsAt,
            canceledAt: null,
        );
    }

    private function assertAtLeastOneItem(): void
    {
        if (empty($this->items)) {
            throw new DomainException('Subscription must have at least one item.');
        }
    }

    private function assertPrimaryItemRequired(): void
    {
        $primaryFound = false;
        foreach ($this->items as $item) {
            if ($item->id()->equals($this->primaryItemId)) {
                $primaryFound = true;
                break;
            }
        }

        if (!$primaryFound) {
            throw new DomainException('Primary item must be one of the subscription items.');
        }
    }


    private function assertAllItemsHaveSameCurrency(): void
    {
        $currencies = array_map(fn(SubscriptionItem $item) => $item->price()->currency()->code(), $this->items);
        if (count(array_unique($currencies)) !== 1) {
            throw new DomainException('All items must have the same currency.');
        }
    }

    private function assertAllItemsHavePeriodDatesWhenActive(): void
    {
        if ($this->status !== SubscriptionStatus::Active) {
            return;
        }

        foreach ($this->items as $item) {
            if ($item->currentPeriodStartsAt() === null || $item->currentPeriodEndsAt() === null) {
                throw new DomainException('All items must have a current period start date when subscription is active.');
            }
        }
    }

    private function assertCanceledMatchesStatus(): void
    {
        if ($this->canceledAt !== null && $this->status !== SubscriptionStatus::Canceled) {
            throw new DomainException('CanceledAt can only be set when subscription is canceled.');
        }
    }
}