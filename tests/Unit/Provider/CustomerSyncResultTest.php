<?php

namespace Tests\Provider;

use AlturaCode\Billing\Core\Provider\CustomerSyncResult;

test('CustomerSyncResult::__construct sets properties correctly', function () {
    $providerCustomerId = 'test-id';
    $metadata = ['key' => 'value'];

    $result = CustomerSyncResult::completed($providerCustomerId, $metadata);

    expect($result->providerCustomerId())->toBe($providerCustomerId)
        ->and($result->metadata())->toBe($metadata);
});

test('CustomerSyncResult::__construct defaults metadata to empty array if not provided', function () {
    $providerCustomerId = 'test-id';

    $result = CustomerSyncResult::completed($providerCustomerId);

    expect($result->providerCustomerId())->toBe($providerCustomerId)
        ->and($result->metadata())->toBe([]);
});