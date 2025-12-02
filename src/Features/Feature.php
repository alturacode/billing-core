<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

final readonly class Feature
{
    public function __construct(
        private FeatureKey  $key,
        private FeatureKind $kind,
        private string      $name,
        private ?string     $description = null,
        private ?string     $unit = null,
    )
    {
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

    public function unit(): ?string
    {
        return $this->unit;
    }
}