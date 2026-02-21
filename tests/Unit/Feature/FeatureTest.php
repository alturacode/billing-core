<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\FeatureUnit;
use AlturaCode\Billing\Core\Features\Feature;

it('can be hydrated', function () {
    $data = [
        'key' => 'feature_key',
        'kind' => 'flag',
        'name' => 'Feature Name',
        'description' => 'Feature Description',
    ];

    $feature = Feature::hydrate($data);

    expect($feature->key()->value())->toBe('feature_key')
        ->and($feature->kind())->toBe(FeatureKind::Flag)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->description())->toBe('Feature Description')
        ->and($feature->hasUnit())->toBeFalse();
});

it('can be hydrated without description and unit', function () {
    $data = [
        'key' => 'feature_key',
        'kind' => 'flag',
        'name' => 'Feature Name',
    ];

    $feature = Feature::hydrate($data);

    expect($feature->key()->value())->toBe('feature_key')
        ->and($feature->kind())->toBe(FeatureKind::Flag)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->description())->toBeNull()
        ->and($feature->hasUnit())->toBeFalse();
});

it('can be hydrated with unit', function () {
    $data = [
        'key' => 'feature_key',
        'kind' => 'limit',
        'name' => 'Feature Name',
        'unit' => 'requests',
    ];

    $feature = Feature::hydrate($data);

    expect($feature->key()->value())->toBe('feature_key')
        ->and($feature->kind())->toBe(FeatureKind::Limit)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->unit()->value())->toBe('requests')
        ->and($feature->hasUnit())->toBeTrue();
});

it('can be created as a flag', function () {
    $key = FeatureKey::fromString('feature_key');
    $feature = Feature::createFlag($key, 'Feature Name');

    expect($feature->key())->toBe($key)
        ->and($feature->kind())->toBe(FeatureKind::Flag)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->isFlag())->toBeTrue()
        ->and($feature->isLimit())->toBeFalse()
        ->and($feature->hasUnit())->toBeFalse();
});

it('can be created as a limit', function () {
    $key = FeatureKey::fromString('feature_key');
    $unit = FeatureUnit::create('requests');
    $feature = Feature::createLimit($key, 'Feature Name', $unit);

    expect($feature->key())->toBe($key)
        ->and($feature->kind())->toBe(FeatureKind::Limit)
        ->and($feature->name())->toBe('Feature Name')
        ->and($feature->isFlag())->toBeFalse()
        ->and($feature->isLimit())->toBeTrue()
        ->and($feature->hasUnit())->toBeTrue()
        ->and($feature->unit())->toBe($unit);
});

it('throws an exception when trying to access unit on a flag', function () {
    $feature = Feature::createFlag(FeatureKey::fromString('feature_key'), 'Feature Name');

    $feature->unit();
})->throws(LogicException::class, 'Flags cannot have units.');

it('can change description', function () {
    $feature = Feature::createFlag(FeatureKey::fromString('feature_key'), 'Feature Name');

    $newFeature = $feature->withDescription('New Description');

    expect($newFeature->description())->toBe('New Description')
        ->and($newFeature->key())->toBe($feature->key())
        ->and($newFeature->kind())->toBe($feature->kind())
        ->and($newFeature->name())->toBe($feature->name())
        ->and($newFeature->hasUnit())->toBe($feature->hasUnit());
});

it('preserves unit when changing description', function () {
    $key = FeatureKey::fromString('feature_key');
    $unit = FeatureUnit::create('requests');
    $feature = Feature::createLimit($key, 'Feature Name', $unit);

    $newFeature = $feature->withDescription('New Description');

    expect($newFeature->description())->toBe('New Description')
        ->and($newFeature->unit())->toBe($unit);
});
