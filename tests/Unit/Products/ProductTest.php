<?php

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductFeature;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPrice;
use AlturaCode\Billing\Core\Products\ProductPriceId;

/**
 * Helpers
 */
function makeProduct(): Product {
    return Product::hydrate([
        'id' => (string) ProductId::generate(),
        'kind' => ProductKind::Plan->value,
        'slug' => 'basic_plan',
        'name' => 'Basic Plan',
        'description' => 'Basic plan description',
        'prices' => [],
        'features' => [],
    ]);
}

function makePrice(?ProductPriceId $id = null, int $amount = 1000, string $currency = 'usd', string $intervalType = 'month', int $intervalCount = 1): ProductPrice {
    $pid = $id ?: ProductPriceId::generate();

    return ProductPrice::hydrate([
        'id' => (string) $pid,
        'price' => ['amount' => $amount, 'currency' => $currency],
        'interval' => ['type' => $intervalType, 'count' => $intervalCount],
    ]);
}

function makePriceArray(ProductPriceId $id, int $amount = 1000, string $currency = 'usd', string $intervalType = 'month', int $intervalCount = 1): array {
    return [
        'id' => (string) $id,
        'price' => ['amount' => $amount, 'currency' => $currency],
        'interval' => ['type' => $intervalType, 'count' => $intervalCount],
    ];
}

function makeFeatureArray(string $key, string $kind, mixed $value, ?string $unit = null, ?int $sortOrder = null, ?string $name = null, ?string $description = null): array {
    return array_filter([
        'key' => $key,
        'kind' => $kind,
        'value' => $value,
        'unit' => $unit,
        'sortOrder' => $sortOrder,
        'name' => $name,
        'description' => $description,
    ], static fn($v) => $v !== null);
}

it('hydrates a product with prices and features', function () {
    $priceId1 = ProductPriceId::generate();
    $priceId2 = ProductPriceId::generate();

    $product = Product::hydrate([
        'id' => (string) ProductId::generate(),
        'kind' => ProductKind::Plan->value,
        'slug' => 'basic_plan',
        'name' => 'Basic Plan',
        'description' => 'Basic plan description',
        'prices' => [
            makePriceArray($priceId1, 1000, 'usd', 'month', 1),
            makePriceArray($priceId2, 10000, 'usd', 'year', 1),
        ],
        'features' => [
            makeFeatureArray('analytics', 'flag', true),
            makeFeatureArray('users', 'limit', 10, 'seats', 1, 'Users'),
        ],
    ]);

    expect($product->kind())->toBe(ProductKind::Plan)
        ->and($product->slug()->value())->toBe('basic_plan')
        ->and($product->name())->toBe('Basic Plan')
        ->and($product->description())->toBe('Basic plan description')
        ->and($product->prices())->toBeArray()->toHaveCount(2)
        ->and($product->prices()[0])->toBeInstanceOf(ProductPrice::class)
        ->and($product->prices()[1])->toBeInstanceOf(ProductPrice::class)
        ->and($product->prices()[0]->id()->value())->toBe((string) $priceId1)
        ->and($product->prices()[1]->id()->value())->toBe((string) $priceId2)
        ->and($product->features())->toBeArray()->toHaveCount(2)
        ->and($product->features()[0])->toBeInstanceOf(ProductFeature::class)
        ->and($product->features()[1])->toBeInstanceOf(ProductFeature::class);
});

test('withPrices returns a new instance and sets prices', function () {
    $product = makeProduct();
    $p1 = makePrice();
    $p2 = makePrice();

    $product2 = $product->withPrices($p1, $p2);

    expect($product2)->not()->toBe($product)
        ->and($product->prices())->toHaveCount(0)
        ->and($product2->prices())->toHaveCount(2)
        ->and($product2->prices()[0]->id()->value())->toBe($p1->id()->value())
        ->and($product2->prices()[1]->id()->value())->toBe($p2->id()->value());
});

test('withFeatures returns a new instance and sets features', function () {
    $product = makeProduct();

    $featureArrays = [
        makeFeatureArray('analytics', 'flag', true),
        makeFeatureArray('users', 'limit', 50, 'seats', 2, 'Users'),
    ];

    // hydrate features through Product::hydrate then swap into base product for assertion simplicity
    $hydrated = Product::hydrate([
        'id' => (string) ProductId::generate(),
        'kind' => ProductKind::Plan->value,
        'slug' => 'tmp_plan',
        'name' => 'Tmp',
        'description' => 'Tmp',
        'prices' => [],
        'features' => $featureArrays,
    ]);

    $product2 = $product->withFeatures(...$hydrated->features());

    expect($product2)->not()->toBe($product)
        ->and($product->features())->toHaveCount(0)
        ->and($product2->features())->toHaveCount(2)
        ->and($product2->features()[0])->toBeInstanceOf(ProductFeature::class)
        ->and($product2->features()[1])->toBeInstanceOf(ProductFeature::class);
});

it('checks price existence with hasPrice and hasAnyPrice', function () {
    $p1 = makePrice();
    $p2 = makePrice();
    $product = makeProduct()->withPrices($p1);

    expect($product->hasPrice($p1->id()))->toBeTrue()
        ->and($product->hasPrice($p2->id()))->toBeFalse();

    $random1 = ProductPriceId::generate();
    $random2 = ProductPriceId::generate();

    expect($product->hasAnyPrice($random1, $p1->id(), $random2))->toBeTrue()
        ->and($product->hasAnyPrice($random1, $random2))->toBeFalse();
});

it('finds a price by id and throws when not found', function () {
    $p1 = makePrice();
    $product = makeProduct()->withPrices($p1);

    $found = $product->findPrice($p1->id());
    expect($found->id()->value())->toBe($p1->id()->value());

    $product->findPrice(ProductPriceId::generate());
})->throws(RuntimeException::class);
