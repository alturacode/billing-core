<?php

use AlturaCode\Billing\Core\Provider\ProductSyncResult;

it('creates empty result', function () {
    $result = ProductSyncResult::makeEmpty();

    expect($result->syncedProductIds())->toBeEmpty()
        ->and($result->syncedPriceIds())->toBeEmpty()
        ->and($result->failedProductIds())->toBeEmpty()
        ->and($result->failedPriceIds())->toBeEmpty()
        ->and($result->syncedPricesCount())->toBe(0)
        ->and($result->failedPricesCount())->toBe(0)
        ->and($result->failedProductsCount())->toBe(0)
        ->and($result->syncedProductsCount())->toBe(0)
        ->and($result->hasFailures())->toBeFalse()
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isPartiallySuccessful())->toBeFalse()
        ->and($result->hasSyncedProducts())->toBeFalse()
        ->and($result->hasSyncedPrices())->toBeFalse()
        ->and($result->metadata())->toBeEmpty()
        ->and($result->isEmpty())->toBeTrue();

    return $result;
});

it('can mark a product as synced', function (ProductSyncResult $result) {
    $result = $result->markSyncedProduct('test-product-id', 'test-provider-product-id');

    expect($result->syncedProductsCount())->toBe(1)
        ->and($result->syncedProductIds())->toHaveKey('test-product-id')
        ->and($result->syncedProductIds()['test-product-id'])->toBe('test-provider-product-id')
        ->and($result->failedProductsCount())->toBe(0)
        ->and($result->hasFailures())->toBeFalse()
        ->and($result->hasSyncedProducts())->toBeTrue()
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isPartiallySuccessful())->toBeFalse()
        ->and($result->isEmpty())->toBeFalse();

    return $result;
})->depends('it creates empty result');

it('can mark a previously synced product as failed', function (ProductSyncResult $result) {
    $result = $result->markFailedProduct('test-product-id', 'test-error-message');

    expect($result->failedProductIds())->toHaveKey('test-product-id')
        ->and($result->failedProductIds()['test-product-id'])->toBe('test-error-message')
        ->and($result->syncedProductsCount())->toBe(0)
        ->and($result->failedProductsCount())->toBe(1)
        ->and($result->hasFailures())->toBeTrue()
        ->and($result->isPartiallySuccessful())->toBeFalse()
        ->and($result->hasSyncedProducts())->toBeFalse()
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->isEmpty())->toBeFalse();

    return $result;
})->depends('it can mark a product as synced');

it('can mark a price as synced', function (ProductSyncResult $result) {
    $result = $result->markSyncedPrice('test-price-id', 'test-provider-price-id');

    expect($result->syncedPricesCount())->toBe(1)
        ->and($result->syncedPriceIds())->toHaveKey('test-price-id')
        ->and($result->syncedPriceIds()['test-price-id'])->toBe('test-provider-price-id')
        ->and($result->failedPriceIds())->toHaveCount(0)
        ->and($result->hasFailures())->toBeFalse()
        ->and($result->hasSyncedPrices())->toBeTrue()
        ->and($result->isPartiallySuccessful())->toBeFalse()
        ->and($result->failedPricesCount())->toBe(0)
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->isEmpty())->toBeFalse();

    return $result;
})->depends('it creates empty result');

it('can mark a previously synced price as failed', function (ProductSyncResult $result) {
    $result = $result->markFailedPrice('test-price-id', 'test-error-message');

    expect($result->failedPriceIds())->toHaveKey('test-price-id')
        ->and($result->failedPriceIds()['test-price-id'])->toBe('test-error-message')
        ->and($result->syncedPriceIds())->toHaveCount(0)
        ->and($result->failedPricesCount())->toBe(1)
        ->and($result->hasFailures())->toBeTrue()
        ->and($result->hasSyncedPrices())->toBeFalse()
        ->and($result->isPartiallySuccessful())->toBeFalse()
        ->and($result->failedProductsCount())->toBe(0)
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->isEmpty())->toBeFalse();

    return $result;
})->depends('it can mark a price as synced');

it('can be partially successful', function (ProductSyncResult $result) {
    $result = $result->markFailedPrice('second-test-price-id', 'test-error-message');

    expect($result->isPartiallySuccessful())->toBeTrue()
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->hasFailures())->toBeTrue();
})->depends('it can mark a price as synced');

it('can add metadata', function (ProductSyncResult $result) {
    $result = $result->addMetadata('test-key', 'test-value');

    expect($result->metadata())->toHaveKey('test-key')
        ->and($result->metadata()['test-key'])->toBe('test-value');
})->depends('it creates empty result');
