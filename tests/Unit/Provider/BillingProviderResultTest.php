<?php

namespace Tests\Provider;

use AlturaCode\Billing\Core\Provider\BillingProviderResult;
use AlturaCode\Billing\Core\Provider\BillingProviderResultClientAction;
use Tests\Fixtures\Subscriptions\SubscriptionMother;

test('redirect method creates BillingProviderResult with redirect client action and given URL', function () {
    $subscription = SubscriptionMother::create();
    $testUrl = 'https://example.com/redirect';

    $result = BillingProviderResult::redirect($subscription, $testUrl);

    expect($result->subscription)->toBe($subscription)
        ->and($result->clientAction)->toBeInstanceOf(BillingProviderResultClientAction::class)
        ->and($result->clientAction->type->isRedirect())->toBeTrue()
        ->and($result->clientAction->url)->toBe($testUrl);
});

test('requiresAction returns true for redirect client action', function () {
    $subscription = SubscriptionMother::create();
    $testUrl = 'https://example.com/redirect';

    $result = BillingProviderResult::redirect($subscription, $testUrl);

    expect($result->requiresAction())->toBeTrue();
});