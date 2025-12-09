<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\FeatureUnit;
use LogicException;

final readonly class Feature
{
    private function __construct(
        private FeatureKey   $key,
        private FeatureKind  $kind,
        private string       $name,
        private ?string      $description = null,
        private ?FeatureUnit $unit = null,
    )
    {
    }

    public static function hydrate(array $data): self
    {
        return new self(
            FeatureKey::fromString($data['key']),
            FeatureKind::from($data['kind']),
            $data['name'],
            $data['description'] ?? null,
            isset($data['unit']) ? FeatureUnit::hydrate($data['unit']) : null
        );
    }

    public static function createFlag(FeatureKey $key, string $name): self
    {
        return new self($key, FeatureKind::Flag, $name);
    }

    public static function createLimit(FeatureKey $key, string $name, FeatureUnit $unit): self
    {
        return new self($key, FeatureKind::Limit, $name, null, $unit);
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

    public function isFlag(): bool
    {
        return $this->kind === FeatureKind::Flag;
    }

    public function isLimit(): bool
    {
        return $this->kind === FeatureKind::Limit;
    }

    public function hasUnit(): bool
    {
        return $this->unit !== null;
    }

    public function unit(): FeatureUnit
    {
        if ($this->kind === FeatureKind::Flag) {
            throw new LogicException('Flags cannot have units.');
        }

        return $this->unit;
    }

    public function withDescription(string $description): self
    {
        return new self($this->key, $this->kind, $this->name, $description, $this->unit);
    }
}