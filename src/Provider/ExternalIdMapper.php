<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    public function map(
        string $type,
        string $internalId,
        string $provider,
        string $externalId
    ): void;

    public function getExternalId(
        string $type,
        string $internalId,
        string $provider
    ): ?string;

    public function getInternalId(
        string $type,
        string $externalId,
        string $provider
    ): ?string;
}