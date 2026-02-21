<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\Address;

it('can be created from scratch', function () {
    $address = Address::from(
        line1: '123 Main St',
        line2: 'Suite 100',
        city: 'New York',
        stateOrProvince: 'NY',
        postalCode: '10001',
        countryCode: 'US'
    );

    expect($address->line1())->toBe('123 Main St')
        ->and($address->line2())->toBe('Suite 100')
        ->and($address->city())->toBe('New York')
        ->and($address->stateOrProvince())->toBe('NY')
        ->and($address->postalCode())->toBe('10001')
        ->and($address->countryCode())->toBe('US');
});

it('can be empty', function () {
    $address = Address::from();

    expect($address->line1())->toBeNull()
        ->and($address->line2())->toBeNull()
        ->and($address->city())->toBeNull()
        ->and($address->stateOrProvince())->toBeNull()
        ->and($address->postalCode())->toBeNull()
        ->and($address->countryCode())->toBeNull();
});

it('validates country code is not empty when provided', function () {
    Address::from(countryCode: '  ');
})->throws(InvalidArgumentException::class, 'Country code cannot be empty when provided.');
