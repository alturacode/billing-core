<?php

declare(strict_types=1);

namespace Tests\Fixtures\Products;

use AlturaCode\Billing\Core\Products\ProductPriceInterval;

final class ProductPriceIntervalMother
{
    public static function monthly(): ProductPriceInterval
    {
        return ProductPriceInterval::monthly();
    }

    public static function yearly(): ProductPriceInterval
    {
        return ProductPriceInterval::yearly();
    }
}
