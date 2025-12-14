<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;

final readonly class Address
{
    private function __construct(
        private ?string $line1,
        private ?string $line2,
        private ?string $city,
        private ?string $stateOrProvince,
        private ?string $postalCode,
        private ?string $countryCode, // ISO-3166-1 alpha-2 typically, e.g. "US"
    )
    {
        if ($this->countryCode !== null) {
            $cc = strtoupper(trim($this->countryCode));
            if ($cc === '') {
                throw new InvalidArgumentException('Country code cannot be empty when provided.');
            }
        }
    }

    public static function from(
        ?string $line1 = null,
        ?string $line2 = null,
        ?string $city = null,
        ?string $stateOrProvince = null,
        ?string $postalCode = null,
        ?string $countryCode = null,
    ): self
    {
        return new self($line1, $line2, $city, $stateOrProvince, $postalCode, $countryCode);
    }

    public function line1(): ?string
    {
        return $this->line1;
    }

    public function line2(): ?string
    {
        return $this->line2;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function stateOrProvince(): ?string
    {
        return $this->stateOrProvince;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function countryCode(): ?string
    {
        return $this->countryCode;
    }
}