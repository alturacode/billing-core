<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Products\ProductFeature;

it('can be created using create method', function () {
    $key = FeatureKey::fromString('feature');
    $value = FeatureValue::flagOn();
    $feature = ProductFeature::create($key, $value);

    expect($feature->key())->toBe($key)
        ->and($feature->value())->toBe($value)
        ->and($feature->name())->toBeNull()
        ->and($feature->description())->toBeNull()
        ->and($feature->sortOrder())->toBe(0);
});

it('can be hydrated', function () {
    $data = [
        'key' => 'feature',
        'value' => ['kind' => 'limit', 'value' => 10],
        'name' => 'Feature Name',
        'description' => 'Feature Description',
        'sortOrder' => 5
    ];

    $feature = ProductFeature::hydrate($data);

    expect($feature->key()->value())->toBe('feature')
        ->and($feature->value()->kind()->isLimit())->toBeTrue()
        ->and($feature->value()->value())->toBe(10)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->description())->toBe('Feature Description')
        ->and($feature->sortOrder())->toBe(5);
});

it('can be customized with name, description and sort order', function () {
    $feature = ProductFeature::create(FeatureKey::fromString('feature'), FeatureValue::flagOn());
    $featureWithName = $feature->withName('Feature Name');
    $featureWithDescription = $featureWithName->withDescription('Feature Description');
    $featureWithSortOrder = $featureWithDescription->withSortOrder(10);
    expect($featureWithSortOrder->name())->toBe('Feature Name')
        ->and($featureWithSortOrder->description())->toBe('Feature Description')
        ->and($featureWithSortOrder->sortOrder())->toBe(10);
});