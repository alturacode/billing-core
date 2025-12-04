<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

enum FeatureKind: string
{
    case Flag = 'flag';
    case Limit = 'limit';
}