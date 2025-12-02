<?php

namespace AlturaCode\Billing\Core\Subscriptions;

enum SubscriptionStatus: string
{
    case Incomplete = 'incomplete';
    case Active = 'active';
    case Paused = 'paused';
    case Canceled = 'canceled';
}
