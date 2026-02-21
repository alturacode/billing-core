<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\FeatureKey;

it('can be created from string', function () {
    $key = FeatureKey::fromString('feature_key');
    expect($key->value())->toBe('feature_key')
        ->and((string) $key)->toBe('feature_key');
});

it('can be hydrated', function () {
    $key = FeatureKey::hydrate('feature_key_2');
    expect($key->value())->toBe('feature_key_2');
});

it('validates format', function () {
    FeatureKey::fromString('Feature-Key');
})->throws(InvalidArgumentException::class, 'Feature key should only contain lowercase letters, numbers and underscores');

it('can check equality', function () {
    $key1 = FeatureKey::fromString('key_1');
    $key2 = FeatureKey::fromString('key_1');
    $key3 = FeatureKey::fromString('key_2');

    expect($key1->equals($key2))->toBeTrue()
        ->and($key1->equals($key3))->toBeFalse();
});
