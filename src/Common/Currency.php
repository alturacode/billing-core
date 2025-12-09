<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;
use Stringable;

final readonly class Currency implements Stringable
{
    private function __construct(private string $code)
    {
        if (!preg_match('/^[a-z]{3}$/', $this->code)) {
            throw new InvalidArgumentException('Currency code should be 3 lowercase letters');
        }
    }

    public static function fromString(string $code): Currency
    {
        return new self($code);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(Currency $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}