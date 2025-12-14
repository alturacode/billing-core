<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    public function store(string $type, string $provider, string|int $internalId, string|int $externalId): void;

    /**
     * @param array<array{type: string, provider: string, internalId: string|int, externalId: string|int}> $data
     */
    public function storeMultiple(array $data): void;

    public function getExternalId(string $type, string $provider, string|int $internalId): string|int|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string|int> $internalIds
     * @return array<string|int>
     */
    public function getExternalIds(string $type, string $provider, array $internalIds): array;

    public function getInternalId(string $type, string $provider, string|int $externalId): string|int|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string|int> $externalIds
     * @return array<string|int>
     */
    public function getInternalIds(string $type, string $provider, array $externalIds): array;
}