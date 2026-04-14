<?php

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsagePolicy;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\Features\InMemoryUsageLedger;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageEventId;

beforeEach(function () {
    $this->ledger = new InMemoryUsageLedger();
    $this->calculator = new UsageWindowCalculator();
});

it('creates usage events with metadata and timestamps', function () {
    $eventId = UsageEventId::generate();
    $billable = BillableIdentity::fromString('user', 42);
    $featureKey = FeatureKey::fromString('comments');
    $recordedAt = new DateTimeImmutable('2026-04-13 15:30:00', new DateTimeZone('UTC'));

    $event = UsageEvent::create(
        $eventId,
        $billable,
        $featureKey,
        7,
        $recordedAt,
        ['source' => 'api', 'request_id' => 'req_123']
    );

    expect($event->id())->toBe($eventId)
        ->and($event->billable())->toBe($billable)
        ->and($event->featureKey())->toBe($featureKey)
        ->and($event->amount())->toBe(7)
        ->and($event->recordedAt()->format('Y-m-d H:i:s'))->toBe('2026-04-13 15:30:00')
        ->and($event->recordedAt()->getTimezone()->getName())->toBe('UTC')
        ->and($event->metadata())->toBe(['source' => 'api', 'request_id' => 'req_123']);
});

it('can be hydrated from persisted data', function () {
    $data = [
        'id' => (string) UsageEventId::generate(),
        'billable' => [
            'type' => 'user',
            'id' => 42,
        ],
        'feature_key' => 'comments',
        'amount' => 9,
        'recorded_at' => '2026-04-13 15:30:00+00:00',
        'metadata' => ['source' => 'import'],
    ];

    $event = UsageEvent::hydrate($data);

    expect($event->billable()->type())->toBe('user')
        ->and($event->billable()->id())->toBe(42)
        ->and($event->featureKey()->value())->toBe('comments')
        ->and($event->amount())->toBe(9)
        ->and($event->metadata())->toBe(['source' => 'import']);
});

it('records usage without requiring an entitlement or policy', function () {
    $event = UsageEvent::create(
        UsageEventId::generate(),
        BillableIdentity::fromString('team', 7),
        FeatureKey::fromString('imports'),
        3,
    );

    expect($this->ledger->record($event))->toBeTrue();
});

it('deduplicates usage events by event id', function () {
    $eventId = UsageEventId::generate();

    $event1 = UsageEvent::create(
        $eventId,
        BillableIdentity::fromString('team', 7),
        FeatureKey::fromString('imports'),
        3,
    );

    $event2 = UsageEvent::create(
        $eventId,
        BillableIdentity::fromString('team', 7),
        FeatureKey::fromString('imports'),
        3,
    );

    expect($this->ledger->record($event1))->toBeTrue()
        ->and($this->ledger->record($event2))->toBeFalse();
});

it('returns zero for new usage', function () {
    $window = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'))
    );

    expect($this->ledger->getUsedAmount(
        BillableIdentity::fromString('team', 7),
        FeatureKey::fromString('imports'),
        $window
    ))->toBe(0);
});

it('sums usage within a window', function () {
    $billable = BillableIdentity::fromString('team', 7);
    $featureKey = FeatureKey::fromString('imports');
    $window = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'))
    );

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        3,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        4,
        new DateTimeImmutable('2026-02-20 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($this->ledger->getUsedAmount($billable, $featureKey, $window))->toBe(7);
});

it('isolates usage by billable identity', function () {
    $featureKey = FeatureKey::fromString('imports');
    $window = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'))
    );

    $billable1 = BillableIdentity::fromString('team', 7);
    $billable2 = BillableIdentity::fromString('team', 8);

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable1,
        $featureKey,
        3,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable2,
        $featureKey,
        5,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($this->ledger->getUsedAmount($billable1, $featureKey, $window))->toBe(3)
        ->and($this->ledger->getUsedAmount($billable2, $featureKey, $window))->toBe(5);
});

it('isolates usage by feature key', function () {
    $billable = BillableIdentity::fromString('team', 7);
    $window = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'))
    );

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        FeatureKey::fromString('imports'),
        3,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        FeatureKey::fromString('exports'),
        5,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($this->ledger->getUsedAmount($billable, FeatureKey::fromString('imports'), $window))->toBe(3)
        ->and($this->ledger->getUsedAmount($billable, FeatureKey::fromString('exports'), $window))->toBe(5);
});

it('isolates usage by month window', function () {
    $billable = BillableIdentity::fromString('team', 7);
    $featureKey = FeatureKey::fromString('imports');
    $februaryWindow = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-02-15 12:00:00', new DateTimeZone('UTC'))
    );
    $marchWindow = $this->calculator->forPolicyAt(
        UsagePolicy::calendarMonth(),
        new DateTimeImmutable('2026-03-15 12:00:00', new DateTimeZone('UTC'))
    );

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        3,
        new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        5,
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($this->ledger->getUsedAmount($billable, $featureKey, $februaryWindow))->toBe(3)
        ->and($this->ledger->getUsedAmount($billable, $featureKey, $marchWindow))->toBe(5);
});

it('supports perpetual windows', function () {
    $billable = BillableIdentity::fromString('team', 7);
    $featureKey = FeatureKey::fromString('websites');
    $window = $this->calculator->forPolicyAt(
        UsagePolicy::perpetual(),
        new DateTimeImmutable('2026-04-15 12:00:00', new DateTimeZone('UTC'))
    );

    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        1,
        new DateTimeImmutable('2026-01-10 12:00:00', new DateTimeZone('UTC')),
    ));
    $this->ledger->record(UsageEvent::create(
        UsageEventId::generate(),
        $billable,
        $featureKey,
        2,
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    ));

    expect($this->ledger->getUsedAmount($billable, $featureKey, $window))->toBe(3);
});
