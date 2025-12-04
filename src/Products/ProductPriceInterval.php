<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use InvalidArgumentException;

final readonly class ProductPriceInterval
{
    private function __construct(
        private string $type,
        private int $count
    )
    {
        if (!in_array($this->type, ['day', 'week', 'month', 'year'])) {
            throw new InvalidArgumentException(sprintf(
                'Incorrect interval "%s". Allowed values are: day, week, month, year',
                $this->type
            ));
        }

        if ($this->count < 1) {
            throw new InvalidArgumentException(sprintf(
                'Interval count must be greater than 0, %d given',
                $this->count
            ));
        }
    }

    public static function hydrate(array $data): self
    {
        return new self($data['type'], $data['count']);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function count(): int
    {
        return $this->count;
    }
}