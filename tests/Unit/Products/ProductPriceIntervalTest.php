<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Products\ProductPriceInterval;

it('can be created using helpers', function () {
    expect(ProductPriceInterval::daily()->type())->toBe('day')
        ->and(ProductPriceInterval::daily()->count())->toBe(1)
        ->and(ProductPriceInterval::weekly()->type())->toBe('week')
        ->and(ProductPriceInterval::weekly()->count())->toBe(1)
        ->and(ProductPriceInterval::biweekly()->type())->toBe('week')
        ->and(ProductPriceInterval::biweekly()->count())->toBe(2)
        ->and(ProductPriceInterval::monthly()->type())->toBe('month')
        ->and(ProductPriceInterval::monthly()->count())->toBe(1)
        ->and(ProductPriceInterval::yearly()->type())->toBe('year')
        ->and(ProductPriceInterval::yearly()->count())->toBe(1);
});

it('can be created using from method', function () {
    $interval = ProductPriceInterval::from('month', 3);
    expect($interval->type())->toBe('month')
        ->and($interval->count())->toBe(3);
});

it('can be hydrated', function () {
    $interval = ProductPriceInterval::hydrate(['type' => 'day', 'count' => 5]);
    expect($interval->type())->toBe('day')
        ->and($interval->count())->toBe(5);
});

it('validates interval type', function () {
    ProductPriceInterval::from('invalid', 1);
})->throws(InvalidArgumentException::class, 'Incorrect interval "invalid". Allowed values are: day, week, month, year');

it('validates interval count', function () {
    ProductPriceInterval::from('month', 0);
})->throws(InvalidArgumentException::class, 'Interval count must be greater than 0, 0 given');

it('can check equality', function () {
    $m1 = ProductPriceInterval::monthly();
    $m2 = ProductPriceInterval::from('month', 1);
    $y = ProductPriceInterval::yearly();

    expect($m1->equals($m2))->toBeTrue()
        ->and($m1->equals($y))->toBeFalse();
});

it('throws exception if hydrate data is not an array', function () {
    ProductPriceInterval::hydrate('not an array');
})->throws(InvalidArgumentException::class, 'ProductPriceInterval::hydrate expects an array.');
