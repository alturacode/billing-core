<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

final readonly class FeatureUnit
{
    private function __construct(
        private string $singular,
        private string $plural
    )
    {
    }

    public static function hydrate(array $data): self
    {
        return new self($data['singular'], $data['plural']);
    }

    public static function create(string $singular, string $plural): self
    {
        return new self($singular, $plural);
    }

    public static function generic(): self
    {
        return self::create('Unit', 'Units');
    }

    public function singular(): string
    {
        return $this->singular;
    }

    public function plural(): string
    {
        return $this->plural;
    }
}