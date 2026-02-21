<?php

declare(strict_types=1);

namespace Tests\Fixtures\Common;

use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\FeatureValue;

final class FeatureValueMother
{
    public static function flag(bool $value = true): FeatureValue
    {
        return FeatureValue::create(FeatureKind::Flag, $value);
    }

    public static function limit(int|string $value = 10): FeatureValue
    {
        return FeatureValue::create(FeatureKind::Limit, $value);
    }
}
