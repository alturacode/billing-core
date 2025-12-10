<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

enum FeatureKind: string
{
    case Flag = 'flag';
    case Limit = 'limit';

    public function isLimit(): bool
    {
        return $this === self::Limit;
    }

    public function isFlag(): bool
    {
        return $this === self::Flag;
    }
}