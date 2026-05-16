# Upgrade Guide: v0.24.* to v0.25.*

This release splits usage recording from usage reading. The usage ledger remains the write path for append-only usage events, while `UsageMeter` is now the read-side abstraction used by entitlement checks.

## What Changed

- `UsageMeter` was added for windowed usage totals.
- `UsageLedger` now extends `UsageMeter`, so existing ledgers still satisfy the read-side contract.
- `UsageAwareEntitlementChecker` now accepts a `UsageMeter` instead of a `UsageLedger`.
- `UsageAwareEntitlementCheckerFactory` now accepts a `UsageMeter` instead of a `UsageLedger`.
- `UsageEvent` amounts remain positive-only.

## Migration Steps

### 1. Keep existing ledgers working

If you already pass a `UsageLedger` to `UsageAwareEntitlementChecker` or `UsageAwareEntitlementCheckerFactory`, no implementation change is required because `UsageLedger` extends `UsageMeter`.

```php
$factory = new UsageAwareEntitlementCheckerFactory(
    new EntitlementResolver(),
    $ledger,
    new UsageWindowCalculator()
);
```

### 2. Use custom meters for resource quotas

For limits that represent current application state, such as active sites, seats, or projects, implement `UsageMeter` directly instead of recording negative events when resources are deleted.

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageMeter;

final class ActiveSiteUsageMeter implements UsageMeter
{
    public function getUsedAmount(
        BillableIdentity $billable,
        FeatureKey $featureKey,
        UsageWindow $window
    ): int {
        // Return count of active sites for $billable from your application database.
    }
}
```

Then pass that meter to the checker factory:

```php
$factory = new UsageAwareEntitlementCheckerFactory(
    new EntitlementResolver(),
    new ActiveSiteUsageMeter(),
    new UsageWindowCalculator()
);
```

### 3. Keep usage events positive

Do not use negative `UsageEvent` amounts for corrections or deletions. `UsageEvent` represents consumed usage, and the constructor still rejects zero and negative amounts.

Use:

- `UsageLedger` for event-summed consumption, such as API calls or monthly exports.
- `UsageMeter` for current-state quotas, such as active sites or seats.

## Notes on Behavior

- `UsageLedger::record()` behavior is unchanged.
- `UsageLedger::getUsedAmount()` remains available through `UsageMeter`.
- Existing code typed against `UsageLedger` continues to work.
- Code that only needs to read usage can now depend on `UsageMeter`.

## Suggested Code Search

If you are migrating a codebase, search for these symbols:

- `UsageAwareEntitlementCheckerFactory(`
- `new UsageAwareEntitlementChecker(`
- `UsageLedger`

Keep `UsageLedger` where you need recording. Use `UsageMeter` where you only need usage totals.

---

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
