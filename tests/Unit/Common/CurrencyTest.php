<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\Currency;

it('can be created from string', function () {
    $currency = Currency::fromString('usd');
    expect($currency->code())->toBe('usd')
        ->and((string) $currency)->toBe('usd');
});

it('can be created from helper', function () {
    $currency = Currency::usd();
    expect($currency->code())->toBe('usd');
});

it('validates currency code format', function () {
    Currency::fromString('US');
})->throws(InvalidArgumentException::class, 'Currency code should be 3 lowercase letters');

it('can check equality', function () {
    $usd1 = Currency::fromString('usd');
    $usd2 = Currency::usd();
    $eur = Currency::fromString('eur');

    expect($usd1->equals($usd2))->toBeTrue()
        ->and($usd1->equals($eur))->toBeFalse();
});
