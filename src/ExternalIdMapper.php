<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;

interface ExternalIdMapper
{
    public function map(
        string               $type,
        string               $internalId,
        SubscriptionProvider $provider,
        string               $externalId
    ): void;

    public function getExternalId(
        string               $type,
        string               $internalId,
        SubscriptionProvider $provider
    ): ?string;

    public function getInternalId(
        string               $type,
        string               $externalId,
        SubscriptionProvider $provider
    ): ?string;
}