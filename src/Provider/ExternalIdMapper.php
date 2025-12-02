<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface ExternalIdMapper
{
    /**
     * @param string $type
     * @param array<array<string, string>> $map
     * @param string $externalId
     * @return void
     */
    public function store(string $type, array  $map, string $externalId): void;

    /**
     * @param string $type
     * @param string|array<string> $internalId
     * @param string $provider
     * @return string|array<array<string, string>>|null
     */
    public function getExternalId(string $type, string|array $internalId, string $provider): string|array|null;

    /**
     * @param string $type
     * @param string|array<string> $externalId
     * @param string $provider
     * @return string|array<array<string, string>>|null
     */
    public function getInternalId(string $type, string|array $externalId, string $provider): string|array|null;
}