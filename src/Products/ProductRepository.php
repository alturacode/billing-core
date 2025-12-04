<?php

namespace AlturaCode\Billing\Core\Products;

interface ProductRepository
{
    public function all(): array;
    public function find(ProductId $productId): ?Product;
    public function findByPriceId(ProductPriceId $priceId): ?Product;

    /**
     * @param array $priceIds
     * @return array<Product>
     */
    public function findMultipleByPriceIds(array $priceIds): array;
    public function save(Product $product): void;
}