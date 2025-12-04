<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use AlturaCode\Billing\Core\Features\FeatureKey;
use AlturaCode\Billing\Core\Features\FeatureKind;
use LogicException;

final readonly class ProductFeature
{
    private function __construct(
        private FeatureKey  $key,
        private FeatureKind $kind,
        private ?int        $limit,
        private bool        $enabledByDefault,
        private ?string     $name = null,
        private ?string     $description = null,
        private ?string     $unit = null,
        private ?int        $sortOrder = 0
    )
    {
        $this->assertValid();
    }

    public static function hydrate(array $data): self
    {
        return new self(
            FeatureKey::fromString($data['key']),
            FeatureKind::from($data['kind']),
            $data['limit'] ?? null,
            $data['enabledByDefault'],
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['unit'] ?? null,
            $data['sortOrder'] ?? 0
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

    public function isEnabledByDefault(): bool
    {
        if ($this->kind !== FeatureKind::Flag) {
            throw new LogicException('Only flag features have enabledByDefault.');
        }

        return $this->enabledByDefault;
    }

    public function isUnlimited(): bool
    {
        if ($this->kind !== FeatureKind::Limit) {
            throw new LogicException('Only limit features can be unlimited.');
        }

        return $this->limit === null;
    }

    public function limit(): ?int
    {
        if ($this->kind !== FeatureKind::Limit) {
            throw new LogicException('Only limit features have a limit.');
        }

        return $this->limit;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function unit(): ?string
    {
        return $this->unit;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    private function assertValid(): void
    {
        if ($this->kind === FeatureKind::Flag && $this->limit !== null) {
            throw new LogicException('Flag features cannot have a limit.');
        }

        if ($this->kind === FeatureKind::Limit && $this->limit !== null && $this->limit < 0) {
            throw new LogicException('Limit cannot be negative.');
        }
    }
}