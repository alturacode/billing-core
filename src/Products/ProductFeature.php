<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Features\FeatureKey;
use AlturaCode\Billing\Core\Features\FeatureKind;
use AlturaCode\Billing\Core\Features\FeatureUnit;
use LogicException;

final readonly class ProductFeature
{
    private function __construct(
        private FeatureKey          $key,
        private FeatureKind         $kind,
        private ProductFeatureValue $value,
        private ?string             $name = null,
        private ?string             $description = null,
        private ?FeatureUnit        $unit = null,
        private ?int                $sortOrder = 0
    )
    {
        $this->assertValid();
    }

    public static function hydrate(array $data): self
    {
        return new self(
            key: FeatureKey::hydrate($data['key']),
            kind: FeatureKind::from($data['kind']),
            value: ProductFeatureValue::hydrate($data['value']),
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            unit: isset($data['unit']) ? FeatureUnit::hydrate($data['unit']) : null,
            sortOrder: $data['sortOrder'] ?? 0
        );
    }

    public function key(): FeatureKey
    {
        return $this->key;
    }

    public function kind(): FeatureKind
    {
        return $this->kind;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function hasUnit(): bool
    {
        return $this->unit !== null;
    }

    public function unit(): FeatureUnit
    {
        if ($this->unit === null) {
            throw new LogicException('Product feature does not have a unit.');
        }

        return $this->unit;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    private function assertValid(): void
    {
        if ($this->kind === FeatureKind::Flag && $this->value->isBoolean() === false) {
            throw new LogicException('Flag features can only have boolean values.');
        }

        if ($this->kind === FeatureKind::Limit && $this->value->isNumeric() === false) {
            throw new LogicException('Limit features can only have numeric values.');
        }
    }
}