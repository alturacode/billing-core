<?php

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Common\BillableIdentity;

interface SubscriptionRepository
{
    public function find(SubscriptionId $subscriptionId): ?Subscription;
    public function findByItemId(SubscriptionItemId $itemId): ?Subscription;
    public function save(Subscription $subscription): void;
    public function findForBillable(
        BillableIdentity $billable,
        SubscriptionName $subscriptionName,
    ): ?Subscription;
    public function findAllForBillable(BillableIdentity $billable): array;
}