<?php

namespace AlturaCode\Billing\Core\Subscriptions;

use AlturaCode\Billing\Core\Common\Billable;

interface SubscriptionRepository
{
    public function find(SubscriptionId $subscriptionId): ?Subscription;
    public function save(Subscription $subscription): void;
    public function findForBillable(
        Billable         $billable,
        SubscriptionName $subscriptionName,
    ): ?Subscription;
    public function findAllForBillable(Billable $billable): array;
}