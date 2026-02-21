<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\Money;
use AlturaCode\Billing\Core\Products\ProductPrice;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;

it('can be created using named constructors', function () {
    $id = ProductPriceId::generate();
    $money = Money::hydrate(['amount' => 1000, 'currency' => 'usd']);

    $monthly = ProductPrice::monthly($id, $money);
    expect($monthly->id())->toBe($id)
        ->and($monthly->price())->toBe($money)
        ->and($monthly->interval()->type())->toBe('month')
        ->and($monthly->interval()->count())->toBe(1);

    $yearly = ProductPrice::yearly($id, $money);
    expect($yearly->interval()->type())->toBe('year')
        ->and($yearly->interval()->count())->toBe(1);
});

it('can be created using create method', function () {
    $id = ProductPriceId::generate();
    $money = Money::hydrate(['amount' => 1000, 'currency' => 'usd']);
    $interval = ProductPriceInterval::daily();

    $price = ProductPrice::create($id, $money, $interval);
    expect($price->id())->toBe($id)
        ->and($price->price())->toBe($money)
        ->and($price->interval())->toBe($interval);
});

it('can be hydrated', function () {
    $id = ProductPriceId::generate()->value();
    $data = [
        'id' => $id,
        'price' => ['amount' => 5000, 'currency' => 'eur'],
        'interval' => ['type' => 'week', 'count' => 2]
    ];

    $price = ProductPrice::hydrate($data);

    expect($price->id()->value())->toBe($id)
        ->and($price->price()->amount())->toBe(5000)
        ->and($price->price()->currency()->code())->toBe('eur')
        ->and($price->interval()->type())->toBe('week')
        ->and($price->interval()->count())->toBe(2);
});

it('throws exception if hydrate data is not an array', function () {
    ProductPrice::hydrate('not an array');
})->throws(InvalidArgumentException::class, 'ProductPrice::hydrate expects an array.');
