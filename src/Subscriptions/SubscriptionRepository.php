<?php

namespace AlturaCode\Billing\Core\Subscriptions;

interface SubscriptionRepository
{
    public function find(SubscriptionId $subscriptionId): ?Subscription;
    public function save(Subscription $subscription): void;
    public function findForCustomer(
        SubscriptionCustomerId $customerId,
        SubscriptionName $subscriptionName,
    ): ?Subscription;
    public function findAllForCustomer(SubscriptionCustomerId $customerId): array;
}