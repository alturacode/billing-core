<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    public function store(string $type, string $provider, array $internalId, string $externalId): void;

    public function storeMultiple(array $data): void;

    public function getExternalId(string $type, string $provider, string $internalId): string|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string> $internalIds
     * @return array<string>
     */
    public function getExternalIds(string $type, string $provider, array $internalIds): array;

    public function getInternalId(string $type, string $provider, string $externalId): string|null;

    /**
     * @param string $type
     * @param string $provider
     * @param array<string> $externalIds
     * @return array<string>
     */
    public function getInternalIds(string $type, string $provider, array $externalIds): array;
}