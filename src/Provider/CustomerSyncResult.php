<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

final readonly class CustomerSyncResult
{
    private function __construct(
        private CustomerSyncResultStatus $status,
        private ?string                  $providerCustomerId,
        private array                    $metadata,
    )
    {
    }

    /** @param array<string, mixed> $metadata */
    public static function success(string $providerCustomerId, array $metadata = []): self
    {
        return new self(CustomerSyncResultStatus::Success, $providerCustomerId, $metadata);
    }

    /** @param array<string, mixed> $metadata */
    public static function failed(array $metadata = []): self
    {
        return new self(CustomerSyncResultStatus::Failed, null, $metadata);
    }

    public function status(): CustomerSyncResultStatus
    {
        return $this->status;
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

    public function isSuccessful(): bool
    {
        return $this->status === CustomerSyncResultStatus::Success;
    }
}