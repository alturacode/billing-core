<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;

final readonly class ProductFeature
{
    private function __construct(
        private FeatureKey   $key,
        private FeatureValue $value,
        private ?string      $name = null,
        private ?string      $description = null,
        private ?int         $sortOrder = 0
    )
    {
    }

    public static function hydrate(array $data): self
    {
        return new self(
            key: FeatureKey::hydrate($data['key']),
            value: FeatureValue::hydrate($data['value']),
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            sortOrder: $data['sortOrder'] ?? 0
        );
    }

    public function key(): FeatureKey
    {
        return $this->key;
    }

    public function value(): FeatureValue
    {
        return $this->value;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
}