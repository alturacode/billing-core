<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class SubscriptionNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Subscription not found')
    {
        parent::__construct($message);
    }
}