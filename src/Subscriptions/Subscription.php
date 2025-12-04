<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

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
     * @param SubscriptionItemId|null $primaryItemId
     * @param DateTimeImmutable $createdAt
     * @param bool $cancelAtPeriodEnd
     * @param DateTimeImmutable|null $trialEndsAt
     * @param DateTimeImmutable|null $canceledAt
     */
    private function __construct(
        private SubscriptionId         $id,
        private SubscriptionCustomerId $customerId,
        private SubscriptionProvider   $provider,
        private SubscriptionName       $name,
        private SubscriptionStatus     $status,
        private array                  $items,
        private ?SubscriptionItemId    $primaryItemId,
        private DateTimeImmutable      $createdAt,
        private bool                   $cancelAtPeriodEnd = false,
        private ?DateTimeImmutable     $trialEndsAt = null,
        private ?DateTimeImmutable     $canceledAt = null,
    )
    {
        $this->assertAtLeastOneItemWhenActive();
        $this->assertPrimaryItemRequired();
        $this->assertAllItemsHaveSameCurrency();
        $this->assertAllItemsHavePeriodDatesWhenActive();
        $this->assertNotDuplicateItems();
        $this->assertCanceledMatchesStatus();
    }

    public static function create(
        SubscriptionId         $id,
        SubscriptionName       $name,
        SubscriptionCustomerId $customerId,
        SubscriptionProvider   $provider,
        ?DateTimeImmutable     $trialEndsAt = null,
    ): Subscription
    {
        return new self(
            id: $id,
            customerId: $customerId,
            provider: $provider,
            name: $name,
            status: SubscriptionStatus::Incomplete,
            items: [],
            primaryItemId: null,
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
            return $this->copy(primaryItemId: $newPrimaryId);
        }

        throw new DomainException('Cannot set primary item to an item that is not part of the subscription.');
    }

    public function changeItemQuantity(SubscriptionItemId $itemId, int $quantity): Subscription
    {
        if (array_any($this->items, fn($item) => $item->id()->equals($itemId))) {
            return $this->copy(items: array_map(
                fn(SubscriptionItem $item) => $item->id()->equals($itemId)
                    ? $item->withQuantity($quantity)
                    : $item,
                $this->items
            ));
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
            return $this->copy(items: array_map(
                fn(SubscriptionItem $item) => $item->id()->equals($itemId)
                    ? $item->withPeriodDates($currentPeriodStartsAt, $currentPeriodEndsAt)
                    : $item,
                $this->items
            ));
        }

        throw new DomainException('Cannot set period dates of item that is not part of the subscription.');
    }

    public function activate(): Subscription
    {
        if ($this->status === SubscriptionStatus::Active) {
            return $this;
        }

        return $this->copy(status: SubscriptionStatus::Active);
    }

    public function cancel(bool $atPeriodEnd = true): Subscription
    {
        if ($this->status === SubscriptionStatus::Canceled) {
            return $this;
        }

        return $this->copy(
            status: $atPeriodEnd ? $this->status : SubscriptionStatus::Canceled,
            cancelAtPeriodEnd: $atPeriodEnd,
            canceledAt: $atPeriodEnd ? null : new DateTimeImmutable(),
        );
    }

    public function pause(): Subscription
    {
        if ($this->status === SubscriptionStatus::Canceled) {
            throw new DomainException('Cannot pause a canceled subscription.');
        }

        return $this->copy(
            status: SubscriptionStatus::Paused,
        );
    }

    public function resume(): Subscription
    {
        if ($this->status === SubscriptionStatus::Active) {
            throw new DomainException('Cannot resume an active subscription.');
        }

        if ($this->status === SubscriptionStatus::Canceled) {
            throw new DomainException('Cannot resume a canceled subscription.');
        }

        return $this->copy(
            status: SubscriptionStatus::Active,
        );
    }

    public function withItems(SubscriptionItem ...$items): Subscription
    {
        return $this->copy(items: $items);
    }

    public function withPrimaryItem(SubscriptionItem $item): Subscription
    {
        if ($item->id()->equals($this->primaryItemId)) {
            return $this;
        }

        if ($this->hasItem($item->id())) {
            return $this->changePrimaryItem($item->id());
        }

        return $this->copy(items: array_merge($this->items, [$item]))->changePrimaryItem($item->id());
    }

    private function assertAtLeastOneItemWhenActive(): void
    {
        if ($this->isActive() && empty($this->items)) {
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

        if ($this->canceledAt === null && $this->status === SubscriptionStatus::Canceled) {
            throw new DomainException('CanceledAt cannot be null when subscription is canceled.');
        }
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function copy(
        ?SubscriptionId         $id = null,
        ?SubscriptionCustomerId $customerId = null,
        ?SubscriptionProvider   $provider = null,
        ?SubscriptionName       $name = null,
        ?SubscriptionStatus     $status = null,
        ?array                  $items = null,
        ?SubscriptionItemId     $primaryItemId = null,
        ?DateTimeImmutable      $createdAt = null,
        ?bool                   $cancelAtPeriodEnd = null,
        ?DateTimeImmutable      $trialEndsAt = null,
        ?DateTimeImmutable      $canceledAt = null,
    ): self
    {
        return new self(
            id: $id ?? $this->id,
            customerId: $customerId ?? $this->customerId,
            provider: $provider ?? $this->provider,
            name: $name ?? $this->name,
            status: $status ?? $this->status,
            items: $items ?? $this->items,
            primaryItemId: $primaryItemId ?? $this->primaryItemId,
            createdAt: $createdAt ?? $this->createdAt,
            cancelAtPeriodEnd: $cancelAtPeriodEnd ?? $this->cancelAtPeriodEnd,
            trialEndsAt: $trialEndsAt ?? $this->trialEndsAt,
            canceledAt: $canceledAt ?? $this->canceledAt,
        );
    }

    private function assertNotDuplicateItems(): void
    {
        $itemIds = array_map(fn(SubscriptionItem $item) => $item->id()->value(), $this->items);
        if (count(array_unique($itemIds)) !== count($itemIds)) {
            throw new DomainException(sprintf('Subscription items must have unique IDs. Duplicate ID found: %s', implode(', ', array_keys(array_filter(array_count_values($itemIds), fn($count) => $count > 1)))));
        }
    }
}