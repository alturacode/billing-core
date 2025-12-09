<?php

use AlturaCode\Billing\Core\Products\ProductFeatureValue;

it('hydrates a truthy boolean value', function () {
    $flag = ProductFeatureValue::hydrate(true);
    expect($flag)->value()->toBeTrue()->and($flag->isBoolean())->toBeTrue()->and($flag->isLimitThreshold())->toBeFalse();
});

it('hydrates a falsy boolean value', function () {
    $flag = ProductFeatureValue::hydrate(false);
    expect($flag)->value()->toBeFalse()->and($flag->isBoolean())->toBeTrue()->and($flag->isLimitThreshold())->toBeFalse();
});

it('hydrates a numeric value', function () {
    $value = ProductFeatureValue::hydrate(10);
    expect($value)->value()->toBe(10)->and($value->isLimitThreshold())->toBeTrue()->and($value->isBoolean())->toBeFalse();
});

it('hydrates an unlimited value', function () {
    $value = ProductFeatureValue::hydrate('unlimited');
    expect($value)->value()->toBe('unlimited')->and($value->isLimitThreshold())->toBeTrue()->and($value->isBoolean())->toBeFalse();
});

it('throws an exception for empty string', fn() => ProductFeatureValue::hydrate(''))->throws(InvalidArgumentException::class);
it('throws an exception for null', fn() => ProductFeatureValue::hydrate(null))->throws(InvalidArgumentException::class);
it('throws an exception for negative value', fn() => ProductFeatureValue::hydrate(-1))->throws(InvalidArgumentException::class);
