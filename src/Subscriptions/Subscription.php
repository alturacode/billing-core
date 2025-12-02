<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\ProductId;
use AlturaCode\Billing\Core\ProductPriceId;
use AlturaCode\Billing\Core\SubscriptionCustomerId;
use AlturaCode\Billing\Core\SubscriptionId;
use AlturaCode\Billing\Core\SubscriptionItemId;
use DateTimeImmutable;
use LogicException;

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
     * @param bool $cancelAtPeriodEnd
     * @param DateTimeImmutable|null $trialEndsAt
     * @param DateTimeImmutable|null $currentPeriodStartsAt
     * @param DateTimeImmutable|null $currentPeriodEndsAt
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
        private ?DateTimeImmutable     $currentPeriodStartsAt = null,
        private ?DateTimeImmutable     $currentPeriodEndsAt = null,
        private ?DateTimeImmutable     $canceledAt = null,
    )
    {
        $this->assertAtLeastOneItem();
        $this->assertPrimaryItemRequired();
        $this->assertValidCancelAtPeriod();
        $this->assertValidPeriod();
    }

    /**
     * @param SubscriptionName $name
     * @param SubscriptionCustomerId $customerId
     * @param ProductId $productId
     * @param ProductPriceId $productPriceId
     * @param SubscriptionProvider $provider
     * @param int $quantity
     * @param DateTimeImmutable|null $trialEndsAt
     * @param array<array{productId: ProductId, priceId: ProductPriceId, quantity: int}> $items
     * @return Subscription
     */
    public static function create(
        SubscriptionName       $name,
        SubscriptionCustomerId $customerId,
        ProductId              $productId,
        ProductPriceId         $productPriceId,
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
                productId: $productId,
                priceId: $productPriceId,
                quantity: $quantity,
            ), ...array_map(fn($item) => new SubscriptionItem(
                id: SubscriptionItemId::generate(),
                productId: $item['productId'],
                priceId: $item['priceId'],
                quantity: $item['quantity'],
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

    public function currentPeriodStartsAt(): ?DateTimeImmutable
    {
        return $this->currentPeriodStartsAt;
    }

    public function currentPeriodEndsAt(): ?DateTimeImmutable
    {
        return $this->currentPeriodEndsAt;
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

        throw new LogicException('Primary item not found.');
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
        foreach ($this->items as $item) {
            if ($item->id()->equals($newPrimaryId)) {
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
                    $this->currentPeriodStartsAt,
                    $this->currentPeriodEndsAt,
                    $this->canceledAt
                );
            }
        }

        throw new LogicException('Cannot set primary item to an item that is not part of the subscription.');
    }

    public function changeItemQuantity(SubscriptionItemId $itemId, int $quantity): Subscription
    {
        foreach ($this->items as $item) {
            if ($item->id()->equals($itemId)) {
                return new self(
                    $this->id,
                    $this->customerId,
                    $this->provider,
                    $this->name,
                    $this->status,
                    array_map(fn(SubscriptionItem $item) => $item->id()->equals($item->id()) ? $item->withQuantity($quantity) : $item, $this->items),
                    $this->primaryItemId,
                    $this->createdAt,
                    $this->cancelAtPeriodEnd,
                    $this->trialEndsAt,
                    $this->currentPeriodStartsAt,
                    $this->currentPeriodEndsAt,
                    $this->canceledAt
                );
            }
        }

        throw new LogicException('Cannot change quantity of item that is not part of the subscription.');
    }

    public function hasTrial(): bool
    {
        return $this->trialEndsAt !== null;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function activate(
        DateTimeImmutable $currentPeriodStartsAt,
        DateTimeImmutable $currentPeriodEndsAt,
    ): Subscription
    {
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
            currentPeriodStartsAt: $currentPeriodStartsAt,
            currentPeriodEndsAt: $currentPeriodEndsAt,
        );
    }

    private function assertAtLeastOneItem(): void
    {
        if (empty($this->items)) {
            throw new LogicException('Subscription must have at least one item.');
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
            throw new LogicException('Primary item must be one of the subscription items.');
        }
    }

    private function assertValidCancelAtPeriod(): void
    {
        if ($this->cancelAtPeriodEnd && $this->currentPeriodEndsAt === null) {
            throw new LogicException('Cannot cancel at period end without a current period end date.');
        }
    }

    private function assertValidPeriod(): void
    {
        if ($this->currentPeriodStartsAt !== null && $this->currentPeriodEndsAt === null) {
            throw new LogicException('Current period end date must be set when start date is present.');
        }

        if ($this->currentPeriodStartsAt === null && $this->currentPeriodEndsAt !== null) {
            throw new LogicException('Current period start date must be set when end date is present.');
        }

        if ($this->currentPeriodStartsAt !== null && $this->currentPeriodEndsAt !== null && $this->currentPeriodStartsAt >= $this->currentPeriodEndsAt) {
            throw new LogicException('Current period end date must be after start date.');
        }
    }
}