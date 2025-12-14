<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use InvalidArgumentException;

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
        $this->ensureNoSyncedAndFailedOverlap();
    }

    /**
     * @param array<string, string> $syncedProductIds A map of internal product id => provider product id
     * @param array<string, string> $syncedPriceIds A map of internal product id => provider price id
     * @param array<string, mixed> $metadata
     */
    public static function completed(array $syncedProductIds, array $syncedPriceIds, array $metadata = []): self
    {
        return new self(
            syncedProductIds: $syncedProductIds,
            syncedPriceIds: $syncedPriceIds,
            failedProductIds: [],
            failedPriceIds: [],
            metadata: $metadata
        );
    }

    /**
     * @param array<string, string> $failedProductIds A map of internal product id => error message
     */
    public function withFailedProducts(array $failedProductIds): self
    {
        return new self(
            syncedProductIds: $this->syncedProductIds,
            syncedPriceIds: $this->syncedPriceIds,
            failedProductIds: $this->failedProductIds + $failedProductIds,
            failedPriceIds: $this->failedPriceIds,
            metadata: $this->metadata
        );
    }

    /**
     * @param array<string, string> $failedPriceIds A map of internal product id => error message
     */
    public function withFailedPrices(array $failedPriceIds): self
    {
        return new self(
            syncedProductIds: $this->syncedProductIds,
            syncedPriceIds: $this->syncedPriceIds,
            failedProductIds: $this->failedProductIds,
            failedPriceIds: $this->failedPriceIds + $failedPriceIds,
            metadata: $this->metadata
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

    public function isSuccessful(): bool
    {
        return !$this->hasFailures();
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    private function ensureNoSyncedAndFailedOverlap(): void
    {
        // Ensure a product is either synced or failed, but not both
        foreach ($this->syncedProductIds as $productId => $_) {
            if (isset($this->failedProductIds[$productId])) {
                throw new InvalidArgumentException('A product cannot be both synced and failed.');
            }
        }

        // Ensure a price is either synced or failed, but not both
        foreach ($this->syncedPriceIds as $productId => $_) {
            if (isset($this->failedPriceIds[$productId])) {
                throw new InvalidArgumentException('A price cannot be both synced and failed.');
            }
        }
    }
}
