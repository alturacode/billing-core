<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use InvalidArgumentException;
use Stringable;

final readonly class Currency implements Stringable
{
    public function __construct(private string $code)
    {
        if (!preg_match('/^[A-Z]{3}$/', $this->code)) {
            throw new InvalidArgumentException('Currency code should be 3 uppercase letters');
        }
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