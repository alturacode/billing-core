<?php

declare(strict_types=1);

namespace Tests\Fixtures\Products;

use AlturaCode\Billing\Core\Products\ProductPriceId;

final class ProductPriceIdMother
{
    public static function create(string $value = '01J6H6J6H6J6H6J6H6J6H6J6H6'): ProductPriceId
    {
        return ProductPriceId::fromString($value);
    }

    public static function random(): ProductPriceId
    {
        return ProductPriceId::generate();
    }
}
