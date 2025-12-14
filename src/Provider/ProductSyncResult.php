<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

final readonly class ProductSyncResult
{
    private function __construct(
        private int   $productsSynced,
        private int   $pricesSynced,
        private array $metadata,
    )
    {
    }

    /** @param array<string, mixed> $metadata */
    public static function completed(int $productsSynced, int $pricesSynced, array $metadata = []): self
    {
        return new self($productsSynced, $pricesSynced, $metadata);
    }

    public function productsSynced(): int
    {
        return $this->productsSynced;
    }

    public function pricesSynced(): int
    {
        return $this->pricesSynced;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
