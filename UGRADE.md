# Upgrade Guide: v0.23.* to v0.24.*

This release is a breaking change. The usage API has been simplified so the usage ledger is now the canonical place to record and query usage.

## What Changed

- `UsageLedger` is now the only public usage write path and also exposes windowed usage totals.
- `UsageAwareEntitlementChecker` is now read-only: it checks entitlements and reads counts from the ledger, but does not record usage.
- `UsageRepository` and `InMemoryUsageRepository` were removed.
- `UsageAwareEntitlementChecker::tryConsume()` was removed.

## Migration Steps

### 1. Replace usage writes

If you previously implemented `UsageRepository`, replace that implementation with `UsageLedger`.

The new write flow is event-based:

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageEventId;
use AlturaCode\Billing\Core\Features\UsageLedger;

$event = UsageEvent::create(
    UsageEventId::generate(),
    BillableIdentity::fromString('user', 123),
    FeatureKey::fromString('projects'),
    1,
    new DateTimeImmutable('now', new DateTimeZone('UTC')),
    ['source' => 'api']
);

$ledger->record($event);
```

`UsageLedger::record()` returns `true` when a new event is appended and `false` when the event id has already been recorded.

If your application previously relied on `tryConsume()` to both authorize and record usage, split that flow:

- authorize with `UsageAwareEntitlementChecker::canUse()`
- record the action with `UsageLedger::record()`

### 2. Update your read/authorization path

Create the checker with the ledger instead of a usage repository:

```php
use AlturaCode\Billing\Core\EntitlementResolver;
use AlturaCode\Billing\Core\UsageAwareEntitlementCheckerFactory;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;

$factory = new UsageAwareEntitlementCheckerFactory(
    new EntitlementResolver(),
    $ledger,
    new UsageWindowCalculator()
);
```

The checker still supports:

- `canUse()` for flag and limit checks
- `getUsedAmount()` for inspecting the current usage window

### 3. Remove old bindings and implementations

Delete or replace any DI/container bindings for:

- `UsageRepository`
- `InMemoryUsageRepository`
- any custom `tryConsume()` call sites

Replace them with:

- `UsageLedger`
- `UsageAwareEntitlementCheckerFactory`

### 4. Adjust tests

Update tests that previously asserted atomic `tryConsume()` behavior:

- verify usage recording through `UsageLedger::record()`
- verify limit checks through `UsageAwareEntitlementChecker::canUse()`
- verify current usage through `UsageAwareEntitlementChecker::getUsedAmount()`

## Notes on Behavior

- There is no built-in atomic consume primitive in v0.24.0.
- If you need stricter concurrency control, implement it in your `UsageLedger` storage backend.
- The ledger query surface is limited to windowed usage totals.

## Suggested Code Search

If you are migrating a codebase, search for these symbols and replace them:

- `UsageRepository`
- `InMemoryUsageRepository`
- `tryConsume(`
