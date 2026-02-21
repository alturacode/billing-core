<?php

declare(strict_types=1);

namespace Tests\Fixtures\Common;

use AlturaCode\Billing\Core\Common\FeatureKey;

final class FeatureKeyMother
{
    public static function create(string $value = 'projects'): FeatureKey
    {
        return FeatureKey::fromString($value);
    }
}
