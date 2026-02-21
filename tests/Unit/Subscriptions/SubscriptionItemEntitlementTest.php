<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlementId;
use AlturaCode\Billing\Core\Common\DateRange;

it('can be created', function () {
    $id = SubscriptionItemEntitlementId::generate();
    $key = FeatureKey::fromString('feature');
    $value = FeatureValue::flagOn();
    $entitlement = SubscriptionItemEntitlement::create($id, $key, $value);

    expect($entitlement->id())->toBe($id)
        ->and($entitlement->key())->toBe($key)
        ->and($entitlement->value())->toBe($value)
        ->and($entitlement->effectiveWindow())->toBeNull();
});

it('can be hydrated', function () {
    $id = SubscriptionItemEntitlementId::generate()->value();
    $data = [
        'id' => $id,
        'key' => 'feature',
        'value' => ['kind' => 'flag', 'value' => true],
        'effective_window' => [
            'start' => '2023-01-01 00:00:00',
            'end' => '2023-12-31 23:59:59'
        ]
    ];

    $entitlement = SubscriptionItemEntitlement::hydrate($data);

    expect($entitlement->id()->value())->toBe($id)
        ->and($entitlement->key()->value())->toBe('feature')
        ->and($entitlement->value()->isOn())->toBeTrue()
        ->and($entitlement->effectiveWindow())->toBeInstanceOf(DateRange::class);
});

it('checks if active at date', function () {
    $id = SubscriptionItemEntitlementId::generate();
    $key = FeatureKey::fromString('feature');
    $value = FeatureValue::flagOn();
    $window = DateRange::hydrate([
        'start' => '2023-01-01 00:00:00',
        'end' => '2023-12-31 23:59:59'
    ]);
    
    $entitlement = SubscriptionItemEntitlement::create($id, $key, $value, $window);
    
    expect($entitlement->isActiveAt(new DateTimeImmutable('2023-06-01')))->toBeTrue()
        ->and($entitlement->isActiveAt(new DateTimeImmutable('2022-12-31')))->toBeFalse()
        ->and($entitlement->isActiveAt(new DateTimeImmutable('2024-01-01')))->toBeFalse();
    
    $noWindow = SubscriptionItemEntitlement::create($id, $key, $value);
    expect($noWindow->isActiveAt(new DateTimeImmutable('2024-01-01')))->toBeTrue();
});
