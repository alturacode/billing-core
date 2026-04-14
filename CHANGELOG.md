# Changelog

All notable changes to this project will be documented in this file.

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
