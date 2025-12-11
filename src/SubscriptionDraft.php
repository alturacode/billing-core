<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use DateTimeImmutable;

final class SubscriptionDraft
{
    public function __construct(
        public string             $name,
        public mixed              $billableId,
        public string             $billableType,
        public string             $provider,
        public int                $quantity = 1,
        public ?string            $plan = null,
        public ?string            $priceId = null,
        public ?string            $intervalType = null,
        public ?int               $intervalCount = null,
        public ?DateTimeImmutable $trialEndsAt = null,
        public array              $addons = [],
    )
    {
    }
}