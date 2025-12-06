<?php

namespace AlturaCode\Billing\Core\Subscriptions;

interface SubscriptionRepository
{
    public function find(SubscriptionId $subscriptionId): ?Subscription;
    public function save(Subscription $subscription): void;
    public function findForBillable(
        SubscriptionBillable $billable,
        SubscriptionName     $subscriptionName,
    ): ?Subscription;
    public function findAllForBillable(SubscriptionBillable $billable): array;
}