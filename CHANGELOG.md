# Changelog

All notable changes to this project will be documented in this file.

## v0.25.0

### Added
- `UsageMeter` read interface for windowed usage totals.

### Changed
- `UsageLedger` now extends `UsageMeter`.
- `UsageAwareEntitlementChecker` no longer reads usage totals directly from a ledger; it reads from a meter.
- The usage-aware factory now accepts a `UsageMeter`.
- Documentation and examples updated to reflect the split between usage recording and usage reading.

### Notes
- `UsageEvent` amounts remain positive; consumers should use custom `UsageMeter` implementations for resource quotas that can decrease when resources are deleted.

## v0.24.0

### Added
- Canonical `UsageLedger` API for recording raw usage events and querying windowed usage totals.
- `UsageEvent` and `UsageEventId` as the standard event model for usage recording.
- Read-only `UsageAwareEntitlementChecker` and `UsageAwareEntitlementCheckerFactory` backed directly by the usage ledger.

### Changed
- Usage recording is now decoupled from entitlement checking.
- `UsageAwareEntitlementChecker` no longer records usage and now only reads usage totals from the ledger.
- The usage-aware factory now accepts a `UsageLedger` instead of a repository abstraction.
- Documentation and examples updated to reflect the ledger-first usage model.

### Removed
- `UsageRepository` and `InMemoryUsageRepository`.
- Write-path methods from `UsageAwareEntitlementChecker`, including `tryConsume()`.

### Notes
- This is a breaking release intended to cleanly separate usage recording from entitlement logic.
- Consumers should migrate their write paths to `UsageLedger::record()` and keep read/authorization logic on `UsageAwareEntitlementChecker`.
