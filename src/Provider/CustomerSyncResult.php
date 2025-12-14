<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

final readonly class CustomerSyncResult
{
    private function __construct(
        private ?string $providerCustomerId,
        private array   $metadata,
    )
    {
    }

    /** @param array<string, mixed> $metadata */
    public static function completed(string $providerCustomerId, array $metadata = []): self
    {
        return new self($providerCustomerId, $metadata);
    }

    public function providerCustomerId(): ?string
    {
        return $this->providerCustomerId;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}