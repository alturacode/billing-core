<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    /**
     * Store or replace an external ID mapping for an internal ID.
     *
     * Implementations must keep mappings one-to-one per type and provider. If the
     * external ID already belongs to a different internal ID, throw
     * ExternalIdMappingConflictException.
     */
    public function store(string $type, string $provider, string|int $internalId, string|int $externalId): void;

    /**
     * @param array<array{type: string, provider: string, internalId: string|int, externalId: string|int}> $data
     */
    public function storeMultiple(array $data): void;

    public function forget(string $type, string $provider, string|int $internalId): void;

    /**
     * @param array<array{type: string, provider: string, internalId: string|int}> $data
     */
    public function forgetMultiple(array $data): void;

    public function getExternalId(string $type, string $provider, string|int $internalId): string|int|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string|int> $internalIds
     * @return array<string|int, string|int|null> - key: internalId, value: externalId
     */
    public function getExternalIdMap(string $type, string $provider, array $internalIds): array;

    public function getInternalId(string $type, string $provider, string|int $externalId): string|int|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string|int> $externalIds
     * @return array<string|int, string|int|null> - key: externalId, value: internalId
     */
    public function getInternalIdMap(string $type, string $provider, array $externalIds): array;
}
