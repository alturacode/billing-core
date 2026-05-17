<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use InvalidArgumentException;

/**
 * @codeCoverageIgnore
 */
final class MemoryExternalIdMapper implements ExternalIdMapper
{
    public function __construct(
        private array $map = []
    ) {
    }

    public function store(string $type, string $provider, int|string $internalId, int|string $externalId): void
    {
        $this->assertNoExternalConflict($this->map, $type, $provider, $internalId, $externalId);

        $this->map[$type][$provider][$internalId] = $externalId;
    }

    public function storeMultiple(array $data): void
    {
        $map = $this->map;

        foreach ($data as $item) {
            $this->assertStoreItem($item);
            $this->assertNoExternalConflict($map, $item['type'], $item['provider'], $item['internalId'], $item['externalId']);

            $map[$item['type']][$item['provider']][$item['internalId']] = $item['externalId'];
        }

        $this->map = $map;
    }

    public function forget(string $type, string $provider, int|string $internalId): void
    {
        unset($this->map[$type][$provider][$internalId]);
    }

    public function forgetMultiple(array $data): void
    {
        $map = $this->map;

        foreach ($data as $item) {
            $this->assertForgetItem($item);

            unset($map[$item['type']][$item['provider']][$item['internalId']]);
        }

        $this->map = $map;
    }

    public function getExternalId(string $type, string $provider, int|string $internalId): string|int|null
    {
        return $this->map[$type][$provider][$internalId] ?? null;
    }

    public function getExternalIdMap(string $type, string $provider, array $internalIds): array
    {
        $result = [];
        foreach ($internalIds as $internalId) {
            $result[$internalId] = $this->getExternalId($type, $provider, $internalId);
        }
        return $result;
    }

    public function getInternalId(string $type, string $provider, int|string $externalId): string|int|null
    {
        foreach ($this->map[$type][$provider] ?? [] as $internalId => $externalIdValue) {
            if ($externalIdValue === $externalId) {
                return $internalId;
            }
        }
        return null;
    }

    public function getInternalIdMap(string $type, string $provider, array $externalIds): array
    {
        $result = [];
        foreach ($externalIds as $externalId) {
            $result[$externalId] = $this->getInternalId($type, $provider, $externalId);
        }
        return $result;
    }

    private function assertNoExternalConflict(
        array $map,
        string $type,
        string $provider,
        int|string $internalId,
        int|string $externalId,
    ): void
    {
        foreach ($map[$type][$provider] ?? [] as $existingInternalId => $existingExternalId) {
            if ($existingExternalId === $externalId && (string) $existingInternalId !== (string) $internalId) {
                throw ExternalIdMappingConflictException::forExternalId(
                    $type,
                    $provider,
                    $externalId,
                    $existingInternalId,
                    $internalId,
                );
            }
        }
    }

    private function assertStoreItem(array $item): void
    {
        if (!isset($item['type'], $item['provider'], $item['internalId'], $item['externalId'])) {
            throw new InvalidArgumentException('Item in data array must contain type, provider, internalId, and externalId fields');
        }
    }

    private function assertForgetItem(array $item): void
    {
        if (!isset($item['type'], $item['provider'], $item['internalId'])) {
            throw new InvalidArgumentException('Item in data array must contain type, provider, and internalId fields');
        }
    }
}
