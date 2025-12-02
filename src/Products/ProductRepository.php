<?php

namespace AlturaCode\Billing\Core;

interface ProductRepository
{
    public function all(): array;
    public function find(ProductId $productId): ?Product;
    public function findByPriceId(ProductPriceId $priceId): ?Product;
    public function save(Product $product): void;
}