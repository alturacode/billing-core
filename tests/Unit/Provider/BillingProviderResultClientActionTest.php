<?php

namespace Tests\Provider;

use AlturaCode\Billing\Core\Provider\BillingProviderResultClientAction;
use AlturaCode\Billing\Core\Provider\BillingProviderResultClientActionType;

test('it creates a redirect client action with a given URL', function () {
    $url = 'https://example.com';
    $clientAction = BillingProviderResultClientAction::redirect($url);

    expect($clientAction->type)->toBe(BillingProviderResultClientActionType::Redirect)
        ->and($clientAction->url)->toBe($url);
});

test('it creates a none client action with a null URL', function () {
    $clientAction = BillingProviderResultClientAction::none();

    expect($clientAction->type)->toBe(BillingProviderResultClientActionType::None)
        ->and($clientAction->url)->toBeNull();
});