<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

final class MemoryExternalIdMapper implements ExternalIdMapper
{
    public function __construct(
        private array $map = []
    ) {
    }

    public function store(string $type, string $provider, int|string $internalId, int|string $externalId): void
    {
        $this->map[$type][$provider][$internalId] = $externalId;
    }

    public function storeMultiple(array $data): void
    {
        foreach ($data as $item) {
            $this->store($item['type'], $item['provider'], $item['internalId'], $item['externalId']);
        }
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
        foreach ($this->map[$type][$provider] as $internalId => $externalIdValue) {
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
}