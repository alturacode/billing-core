<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

enum UsagePeriod: string
{
    case Month = 'month';
    case Perpetual = 'perpetual';
    // Future: Day, Week, Year
}
