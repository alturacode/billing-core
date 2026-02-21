<?php

declare(strict_types=1);

namespace Tests\Fixtures\Common;

use AlturaCode\Billing\Core\Common\DateRange;
use DateTimeImmutable;

final class DateRangeMother
{
    public static function create(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): DateRange
    {
        return DateRange::from($start, $end);
    }
}
