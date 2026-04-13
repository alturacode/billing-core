<?php

declare(strict_types=1);

namespace Tests\Fixtures\Subscriptions;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionTrialPolicy;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;
use DateTimeImmutable;
use ReflectionClass;
use Tests\Fixtures\Common\BillableIdentityMother;

final class SubscriptionMother
{
    /**
     * @param array<SubscriptionItem>|null $items
     */
    public static function create(
        ?SubscriptionId       $id = null,
        ?BillableIdentity     $billable = null,
        ?SubscriptionProvider $provider = null,
        ?SubscriptionName     $name = null,
        ?SubscriptionStatus   $status = null,
        ?array                $items = null,
        ?SubscriptionItemId   $primaryItemId = null,
        ?DateTimeImmutable    $createdAt = null,
        bool                  $cancelAtPeriodEnd = false,
        ?DateTimeImmutable    $trialEndsAt = null,
        ?SubscriptionTrialPolicy $trialPolicy = null,
        ?DateTimeImmutable    $canceledAt = null,
    ): Subscription {
        $reflection = new ReflectionClass(Subscription::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        
        $id = $id ?? SubscriptionIdMother::random();
        $billable = $billable ?? BillableIdentityMother::create();
        $provider = $provider ?? SubscriptionProviderMother::create();
        $name = $name ?? SubscriptionNameMother::create();
        $status = $status ?? SubscriptionStatus::Active;
        $items = $items ?? [SubscriptionItemMother::create()];
        $primaryItemId = $primaryItemId ?? $items[0]->id();
        $createdAt = $createdAt ?? new DateTimeImmutable();

        // If status is canceled, we must have a canceledAt date
        if ($status === SubscriptionStatus::Canceled && $canceledAt === null) {
            $canceledAt = new DateTimeImmutable();
        }

        $constructor->invoke($instance,
            $id,
            $billable,
            $provider,
            $name,
            $status,
            $items,
            $primaryItemId,
            $createdAt,
            $cancelAtPeriodEnd,
            $trialEndsAt,
            $trialPolicy,
            $canceledAt,
        );

        return $instance;
    }

    public static function active(): Subscription
    {
        return self::create(status: SubscriptionStatus::Active);
    }

    public static function incomplete(): Subscription
    {
        return self::create(
            status: SubscriptionStatus::Incomplete,
            items: [],
            primaryItemId: null
        );
    }

    public static function canceled(): Subscription
    {
        return self::create(
            status: SubscriptionStatus::Canceled,
            canceledAt: new DateTimeImmutable()
        );
    }

    public static function paused(): Subscription
    {
        return self::create(status: SubscriptionStatus::Paused);
    }
}
