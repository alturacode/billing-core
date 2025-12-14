<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use InvalidArgumentException;
use LogicException;

final readonly class ProductSyncResult
{
    /**
     * @param array<string, string> $syncedProductIds A map of internal product id => provider product id
     * @param array<string, string> $syncedPriceIds A map of internal product id => provider price id
     * @param array<string, string> $failedProductIds A map of internal product id => error message
     * @param array<string, string> $failedPriceIds A map of internal product id => error message
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        private array $syncedProductIds,
        private array $syncedPriceIds,
        private array $failedProductIds,
        private array $failedPriceIds,
        private array $metadata,
    )
    {
    }

    public static function makeEmpty(): self
    {
        return new self(
            syncedProductIds: [],
            syncedPriceIds: [],
            failedProductIds: [],
            failedPriceIds: [],
            metadata: []
        );
    }

    public function markSyncedProduct(string $internalProductId, string $providerProductId): self
    {
        $failedProductIds = $this->failedProductIds;
        unset($failedProductIds[$internalProductId]);

        return new self(
            syncedProductIds: $this->syncedProductIds + [$internalProductId => $providerProductId],
            syncedPriceIds: $this->syncedPriceIds,
            failedProductIds: $failedProductIds,
            failedPriceIds: $this->failedPriceIds,
            metadata: $this->metadata
        );
    }

    public function markFailedProduct(string $internalProductId, string $errorMessage): self
    {
        $syncedProductIds = $this->syncedProductIds;
        unset($syncedProductIds[$internalProductId]);

        return new self(
            syncedProductIds: $syncedProductIds,
            syncedPriceIds: $this->syncedPriceIds,
            failedProductIds: $this->failedProductIds + [$internalProductId => $errorMessage],
            failedPriceIds: $this->failedPriceIds,
            metadata: $this->metadata
        );
    }

    public function markFailedPrice(string $internalProductId, string $errorMessage): self
    {
        $syncedPriceIds = $this->syncedPriceIds;
        unset($syncedPriceIds[$internalProductId]);

        return new self(
            syncedProductIds: $this->syncedProductIds,
            syncedPriceIds: $syncedPriceIds,
            failedProductIds: $this->failedProductIds,
            failedPriceIds: $this->failedPriceIds + [$internalProductId => $errorMessage],
            metadata: $this->metadata
        );
    }

    public function markSyncedPrice(string $internalProductId, string $providerPriceId): self
    {
        $failedPriceIds = $this->failedPriceIds;
        unset($failedPriceIds[$internalProductId]);

        return new self(
            syncedProductIds: $this->syncedProductIds,
            syncedPriceIds: $this->syncedPriceIds + [$internalProductId => $providerPriceId],
            failedProductIds: $this->failedProductIds,
            failedPriceIds: $failedPriceIds,
            metadata: $this->metadata
        );
    }

    public function addMetadata(string $key, mixed $value): self
    {
        return new self(
            syncedProductIds: $this->syncedProductIds,
            syncedPriceIds: $this->syncedPriceIds,
            failedProductIds: $this->failedProductIds,
            failedPriceIds: $this->failedPriceIds,
            metadata: $this->metadata + [$key => $value]
        );
    }

    /**
     * @return array<string, string>
     */
    public function syncedProductIds(): array
    {
        return $this->syncedProductIds;
    }

    /**
     * @return array<string, string>
     */
    public function syncedPriceIds(): array
    {
        return $this->syncedPriceIds;
    }

    /**
     * @return array<string, string>
     */
    public function failedProductIds(): array
    {
        return $this->failedProductIds;
    }

    /**
     * @return array<string, string>
     */
    public function failedPriceIds(): array
    {
        return $this->failedPriceIds;
    }

    public function syncedProductsCount(): int
    {
        return count($this->syncedProductIds);
    }

    public function syncedPricesCount(): int
    {
        return count($this->syncedPriceIds);
    }

    public function failedProductsCount(): int
    {
        return count($this->failedProductIds);
    }

    public function failedPricesCount(): int
    {
        return count($this->failedPriceIds);
    }

    public function hasFailures(): bool
    {
        return $this->failedProductsCount() > 0 || $this->failedPricesCount() > 0;
    }

    public function hasSyncedProducts(): bool
    {
        return $this->syncedProductsCount() > 0;
    }

    public function hasSyncedPrices(): bool
    {
        return $this->syncedPricesCount() > 0;
    }

    public function isPartiallySuccessful(): bool
    {
        return ($this->syncedProductsCount() > 0 || $this->syncedPricesCount() > 0) && $this->hasFailures();
    }

    public function isSuccessful(): bool
    {
        return !$this->hasFailures();
    }

    public function isEmpty(): bool
    {
        return $this->syncedProductsCount() === 0
            && $this->syncedPricesCount() === 0
            && $this->failedProductsCount() === 0
            && $this->failedPricesCount() === 0;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
