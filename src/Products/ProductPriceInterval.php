<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use InvalidArgumentException;

final readonly class ProductPriceInterval
{
    public function __construct(
        private string $interval,
        private int    $intervalCount
    )
    {
        if (!in_array($this->interval, ['day', 'week', 'month', 'year'])) {
            throw new InvalidArgumentException(sprintf(
                'Incorrect interval "%s". Allowed values are: day, week, month, year',
                $this->interval
            ));
        }

        if ($this->intervalCount < 1) {
            throw new InvalidArgumentException(sprintf(
                'Interval count must be greater than 0, %d given',
                $this->intervalCount
            ));
        }
    }
}