<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\BillableIdentity;

it('can be created from string', function () {
    $identity = BillableIdentity::fromString('user', 123);
    expect($identity->type())->toBe('user')
        ->and($identity->id())->toBe(123);
});

it('can be hydrated', function () {
    $identity = BillableIdentity::hydrate(['type' => 'org', 'id' => 'abc']);
    expect($identity->type())->toBe('org')
        ->and($identity->id())->toBe('abc');
});

it('validates type is not empty', function () {
    BillableIdentity::fromString('', 123);
})->throws(InvalidArgumentException::class, 'Billable type cannot be empty');

it('validates id is not null', function () {
    BillableIdentity::fromString('user', null);
})->throws(InvalidArgumentException::class, 'Billable id cannot be null');

it('validates id is string or int', function () {
    BillableIdentity::fromString('user', 1.23);
})->throws(InvalidArgumentException::class, 'Billable id should be a string or integer');
