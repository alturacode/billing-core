<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use Stringable;

final readonly class FeatureUnit implements Stringable
{
    private function __construct(
        private string $value
    )
    {
    }

    public static function hydrate(mixed $data): self
    {
        return new self($data);
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function generic(): self
    {
        return self::create('unit');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}