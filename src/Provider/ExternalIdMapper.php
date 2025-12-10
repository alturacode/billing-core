<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    public function store(string $type, array $internalId, string $externalId): void;

    public function storeMultiple(array $data): void;

    public function getExternalId(string $type, string $internalId, string $provider): string|null;

    /**
     * @param string $type
     * @param array $internalIds
     * @param string $provider
     * @return array<string>
     */
    public function getExternalIds(string $type, array $internalIds, string $provider): array;

    public function getInternalId(string $type, string $externalId, string $provider): string|null;

    /**
     * @param string $type
     * @param array<string> $externalIds
     * @param string $provider
     * @return array<string>
     */
    public function getInternalIds(string $type, array $externalIds, string $provider): array;
}