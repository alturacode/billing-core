<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\Address;
use AlturaCode\Billing\Core\Common\BillableDetails;

it('can be created from constructor or empty helper', function () {
    $details = BillableDetails::from(
        displayName: 'John Doe',
        email: 'john@example.com',
        phone: '+123456789',
        locales: ['en', 'es'],
        billingAddress: Address::from(line1: '123 Main St'),
        metadata: ['key' => 'value']
    );

    expect($details->displayName())->toBe('John Doe')
        ->and($details->email())->toBe('john@example.com')
        ->and($details->phone())->toBe('+123456789')
        ->and($details->locales())->toBe(['en', 'es'])
        ->and($details->billingAddress()->line1())->toBe('123 Main St')
        ->and($details->metadata())->toBe(['key' => 'value']);

    $empty = BillableDetails::empty();
    expect($empty->displayName())->toBeNull()
        ->and($empty->email())->toBeNull()
        ->and($empty->phone())->toBeNull()
        ->and($empty->locales())->toBeNull()
        ->and($empty->billingAddress())->toBeNull()
        ->and($empty->metadata())->toBe([]);
});

it('can add metadata', function () {
    $details = BillableDetails::empty();
    $newDetails = $details->withMetadata('foo', 'bar');

    expect($newDetails->metadata())->toBe(['foo' => 'bar'])
        ->and($details->metadata())->toBe([]);
});

it('validates email is not empty when provided', function () {
    BillableDetails::from(email: '  ');
})->throws(InvalidArgumentException::class, 'Email cannot be empty when provided.');

it('validates display name is not empty when provided', function () {
    BillableDetails::from(displayName: '  ');
})->throws(InvalidArgumentException::class, 'Display name cannot be empty when provided.');

it('validates phone is not empty when provided', function () {
    BillableDetails::from(phone: '  ');
})->throws(InvalidArgumentException::class, 'Phone cannot be empty when provided.');

it('validates locales is not empty when provided', function () {
    BillableDetails::from(locales: []);
})->throws(InvalidArgumentException::class, 'Locales cannot be empty when provided.');

it('validates locales are strings', function () {
    BillableDetails::from(locales: ['en', 123]);
})->throws(InvalidArgumentException::class, 'Locales must be an array of strings.');

it('validates metadata values are scalar', function () {
    BillableDetails::from(metadata: ['key' => ['array']]);
})->throws(InvalidArgumentException::class, "Metadata value for key 'key' must be scalar.");
