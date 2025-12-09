<?php

use AlturaCode\Billing\Core\Money;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductPriceInterval;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;

/**
 * Helpers
 */
function makeMoneyForItem(int $amount = 1000, string $currency = 'usd'): Money {
    return Money::hydrate(['amount' => $amount, 'currency' => $currency]);
}

function makeSubscriptionItem(int $quantity = 1, string $currency = 'usd'): SubscriptionItem {
    return SubscriptionItem::create(
        id: SubscriptionItemId::generate(),
        priceId: ProductPriceId::generate(),
        quantity: $quantity,
        price: makeMoneyForItem(1000, $currency),
        interval: ProductPriceInterval::monthly(),
    );
}

it('creates an item and exposes its getters', function () {
    $id = SubscriptionItemId::generate();
    $priceId = ProductPriceId::generate();
    $price = makeMoneyForItem(1500, 'usd');

    $item = SubscriptionItem::create(
        id: $id,
        priceId: $priceId,
        quantity: 2,
        price: $price,
        interval: ProductPriceInterval::monthly(),
    );

    expect($item->id()->value())->toBe((string) $id)
        ->and($item->priceId()->equals($priceId))->toBeTrue()
        ->and($item->quantity())->toBe(2)
        ->and($item->price()->amount())->toBe(1500)
        ->and($item->price()->currency()->code())->toBe('usd')
        ->and($item->currentPeriodStartsAt())->toBeNull()
        ->and($item->currentPeriodEndsAt())->toBeNull()
        ->and($item->interval()->type())->toBe('month')
        ->and($item->interval()->count())->toBe(1);
});

test('withQuantity returns a new instance and updates quantity', function () {
    $item = makeSubscriptionItem(1);
    $updated = $item->withQuantity(3);

    expect($updated)->not()->toBe($item)
        ->and($item->quantity())->toBe(1)
        ->and($updated->quantity())->toBe(3);
});

it('throws on quantity less than 1', function () {
    SubscriptionItem::create(
        id: SubscriptionItemId::generate(),
        priceId: ProductPriceId::generate(),
        quantity: 0,
        price: makeMoneyForItem(),
        interval: ProductPriceInterval::monthly(),
    );
})->throws(InvalidArgumentException::class);

test('withPeriodDates sets valid period dates', function () {
    $item = makeSubscriptionItem(1);
    $start = new DateTimeImmutable('2025-01-01 00:00:00');
    $end = new DateTimeImmutable('2025-02-01 00:00:00');

    $withPeriod = $item->withPeriodDates($start, $end);

    expect($withPeriod->currentPeriodStartsAt())->toEqual($start)
        ->and($withPeriod->currentPeriodEndsAt())->toEqual($end)
        ->and($item->currentPeriodStartsAt())->toBeNull()
        ->and($item->currentPeriodEndsAt())->toBeNull();
});

it('hydrate throws when only start date is present', function () {
    $data = [
        'id' => (string) SubscriptionItemId::generate(),
        'price_id' => (string) ProductPriceId::generate(),
        'quantity' => 1,
        'price' => ['amount' => 1000, 'currency' => 'usd'],
        'interval' => ['type' => 'month', 'count' => 1],
        'current_period_starts_at' => '2025-01-01 00:00:00',
    ];

    SubscriptionItem::hydrate($data);
})->throws(DomainException::class);

it('hydrate throws when only end date is present', function () {
    $data = [
        'id' => (string) SubscriptionItemId::generate(),
        'price_id' => (string) ProductPriceId::generate(),
        'quantity' => 1,
        'price' => ['amount' => 1000, 'currency' => 'usd'],
        'interval' => ['type' => 'month', 'count' => 1],
        'current_period_ends_at' => '2025-02-01 00:00:00',
    ];

    SubscriptionItem::hydrate($data);
})->throws(DomainException::class);

it('hydrate throws when end is not after start (equal or before)', function () {
    $data = [
        'id' => (string) SubscriptionItemId::generate(),
        'price_id' => (string) ProductPriceId::generate(),
        'quantity' => 1,
        'price' => ['amount' => 1000, 'currency' => 'usd'],
        'interval' => ['type' => 'month', 'count' => 1],
        'current_period_starts_at' => '2025-01-01 00:00:00',
        'current_period_ends_at' => '2025-01-01 00:00:00', // equal to start
    ];

    SubscriptionItem::hydrate($data);
})->throws(DomainException::class);

it('hydrates successfully with valid dates', function () {
    $id = SubscriptionItemId::generate();
    $priceId = ProductPriceId::generate();
    $startStr = '2025-01-01 00:00:00';
    $endStr = '2025-02-01 00:00:00';

    $item = SubscriptionItem::hydrate([
        'id' => (string) $id,
        'price_id' => (string) $priceId,
        'quantity' => 2,
        'price' => ['amount' => 2000, 'currency' => 'usd'],
        'interval' => ['type' => 'month', 'count' => 1],
        'current_period_starts_at' => $startStr,
        'current_period_ends_at' => $endStr,
    ]);

    expect($item->id()->value())->toBe((string) $id)
        ->and($item->priceId()->equals($priceId))->toBeTrue()
        ->and($item->quantity())->toBe(2)
        ->and($item->price()->amount())->toBe(2000)
        ->and($item->price()->currency()->code())->toBe('usd')
        ->and($item->interval()->type())->toBe('month')
        ->and($item->interval()->count())->toBe(1)
        ->and($item->currentPeriodStartsAt()?->format('Y-m-d H:i:s'))->toBe($startStr)
        ->and($item->currentPeriodEndsAt()?->format('Y-m-d H:i:s'))->toBe($endStr);
});
