<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Products\Product;

interface ProductAwareBillingProvider extends BillingProvider
{
    public function syncProduct(Product $product, array $options = []): ProductSyncResult;

    /**
     * @param array<Product> $products
     */
    public function syncProducts(array $products, array $options = []): ProductSyncResult;
}
