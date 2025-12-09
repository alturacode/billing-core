<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\FeatureKey;

interface FeatureRepository
{
    public function all(): array;
    public function find(FeatureKey $key): ?Feature;
}