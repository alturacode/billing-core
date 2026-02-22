<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

enum UsagePeriod: string
{
    case Month = 'month';
    // Future: Day, Week, Year
}
