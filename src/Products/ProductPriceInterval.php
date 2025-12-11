<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Products;

use InvalidArgumentException;

final readonly class ProductPriceInterval
{
    private function __construct(
        private string $type,
        private int    $count
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

    public static function hydrate(mixed $data): self
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('ProductPriceInterval::hydrate expects an array.');
        }

        return new self($data['type'], $data['count']);
    }

    public static function from(string $type, int $count): self
    {
        return new self($type, $count);
    }

    public function equals(ProductPriceInterval $other): bool
    {
        return $this->type === $other->type && $this->count === $other->count;
    }

    public static function daily(): self
    {
        return new self('day', 1);
    }

    public static function weekly(): self
    {
        return new self('week', 1);
    }

    public static function biweekly(): self
    {
        return new self('week', 2);
    }

    public static function monthly(): self
    {
        return new self('month', 1);
    }

    public static function yearly(): self
    {
        return new self('year', 1);
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