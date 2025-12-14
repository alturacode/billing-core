<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;

final readonly class BillableDetails
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    private function __construct(
        private ?string  $displayName,
        private ?string  $email,
        private ?string  $phone,
        private ?array   $locales, // e.g. "en", "en_US", "de-DE" (leave validation to adapter/provider)
        private ?Address $billingAddress,
        private array    $metadata,
    )
    {
        if ($this->email !== null && trim($this->email) === '') {
            throw new InvalidArgumentException('Email cannot be empty when provided.');
        }

        if ($this->displayName !== null && trim($this->displayName) === '') {
            throw new InvalidArgumentException('Display name cannot be empty when provided.');
        }

        if ($this->phone !== null && trim($this->phone) === '') {
            throw new InvalidArgumentException('Phone cannot be empty when provided.');
        }

        if ($this->locales !== null && count($this->locales) === 0) {
            throw new InvalidArgumentException('Locales cannot be empty when provided.');
        }

        foreach ($this->locales ?? [] as $locale) {
            if (!is_string($locale)) {
                throw new InvalidArgumentException('Locales must be an array of strings.');
            }
        }

        foreach ($this->metadata as $key => $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException("Metadata value for key '$key' must be scalar.");
            }
        }
    }

    /**
     * Named constructor that stays flexible as fields evolve.
     *
     * @param array<string, scalar|null> $metadata
     */
    public static function from(
        ?string  $displayName = null,
        ?string  $email = null,
        ?string  $phone = null,
        ?array   $locales = null,
        ?Address $billingAddress = null,
        array    $metadata = [],
    ): self
    {
        return new self(
            displayName: $displayName,
            email: $email,
            phone: $phone,
            locales: $locales,
            billingAddress: $billingAddress,
            metadata: $metadata,
        );
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, []);
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function locales(): ?array
    {
        return $this->locales;
    }

    public function billingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /** @return array<string, scalar|null> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(string $key, string|int|float|bool|null $value): self
    {
        $meta = $this->metadata;
        $meta[$key] = $value;

        return new self(
            displayName: $this->displayName,
            email: $this->email,
            phone: $this->phone,
            locales: $this->locales,
            billingAddress: $this->billingAddress,
            metadata: $meta,
        );
    }
}