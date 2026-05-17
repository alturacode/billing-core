# AlturaCode Billing Core

Framework-agnostic billing engine for PHP apps. Designed for multiple billing providers and framework adapters.

This package contains the **core billing domain and orchestration logic**:

- Products, prices, intervals, and features
- Subscriptions, subscription items, entitlements and feature usage
- Provider-agnostic subscription workflows (create, cancel, pause, resume)
- A pluggable provider registry so you can swap Stripe, PayPal, or any other gateway

Framework-specific integrations (for example, Laravel) and concrete billing providers (for example, Stripe) live in **separate packages**, such as:

- `alturacode/billing-stripe` — [Stripe provider](https://github.com/alturacode/billing-stripe)
- `alturacode/billing-paypal` — Stripe provider implementation (planned)
- `alturacode/billing-laravel` — [Laravel adapter](https://github.com/alturacode/billing-laravel)

This README focuses on the **core package** and doubles as its main documentation.

---

## TL;DR

```bash
composer require alturacode/billing-core
composer require alturacode/billing-stripe # or another provider package
```

1. Implement `ProductRepository` and `SubscriptionRepository` for your storage.
2. Implement or install one or more `BillingProvider` implementations (for example, Stripe).
3. Register providers in `BillingProviderRegistry`.
4. Use `BillingManager` to create, cancel, pause, and resume subscriptions.

```php
use AlturaCode\Billing\Core\BillingManager;
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Core\SubscriptionDraftBuilder;

$providerRegistry = new BillingProviderRegistry([
    // 'stripe' => new StripeBillingProvider(...),
]);

$subscriptions = new DatabaseSubscriptionRepository(...);
$products      = new DatabaseProductRepository(...);

$billing = new BillingManager($products, $subscriptions, $providerRegistry);

$result = $billing->createSubscription(
    new SubscriptionDraftBuilder()
        ->withName('default')
        ->withBillable('user', '123') 
        ->withProvider('stripe')
        ->withPlanPriceId('plan_ulid')
        ->withAddon('addon_ulid', 5)
        ->withTrialDays(15)
        ->build();
);

if ($result->isSuccessful()) {
    // Access $result->subscription
}
```

---

## Package Goals

- **Framework-agnostic**: Works in plain PHP, Laravel, Symfony, or any custom framework.
- **Provider-pluggable**: Stripe, PayPal, or your in-house gateway via a clean provider interface.
- **Domain-centric**: Strongly typed domain objects, not arrays everywhere.
- **Minimal surface**: Focused on subscriptions and recurring billing flows.

This package intentionally **does not**:

- Talk directly to Stripe/PayPal APIs (that’s the job of provider packages)
- Manage HTTP controllers or routing (that’s the job of framework adapters)
- Decide how you store data (you implement the repositories)

---

## Installation

```bash
composer require alturacode/billing-core
```

Core requirements:

- PHP `>= 8.2`
- `symfony/uid` for strongly-typed IDs (ULID/UUID)

To actually connect to a billing gateway, install at least one **provider**:

```bash
composer require alturacode/billing-stripe
```

And, if you want framework helpers, install a **framework adapter**, for example:

```bash
composer require alturacode/billing-laravel
```

---

## Core Concepts

The core package exposes a small set of domain concepts. The namespaces below are simplified; look in `src/` for full details.

### Money & Currency

- `AlturaCode\Billing\Core\Common\Money`
- `AlturaCode\Billing\Core\Common\Currency`

These model monetary values and currencies. Prices on products are expressed with these types.

### Billable

- `AlturaCode\Billing\Core\Common\BillableIdentity` — polymorphic-style identifier of your customer in your own system
- `AlturaCode\Billing\Core\Common\BillableDetails` — a description of your customer in your own system, this is used to pass customer details to the provider

### Products & Prices

Located under `AlturaCode\Billing\Core\Products`:

- `Product` — a billable thing (plan, add-on, one-off, etc.)
- `ProductId` — identifier for a product
- `ProductKind` — enum-like type describing the kind of product (for example, Plan, Addon)
- `ProductPrice` — a specific price for a product
- `ProductPriceId` — identifier for a price
- `ProductPriceInterval` — billing interval (for example, monthly, yearly)
- `ProductFeature` — mapping between products and features
- `ProductRepository` — abstraction that you implement to load and query products from your DB or configuration

You are free to map these to your own product tables, configuration files, or external catalogs.

### Features

Located under `AlturaCode\Billing\Core\Features`:

- `Feature` — a capability your subscription unlocks (for example, `projects`, `seats`, `storage_gb`)
- `UsagePolicy` — defines how usage is tracked and reset for limit-based features. By default, limits use a `calendarMonth` policy.
- `UsageMeter` — read-side abstraction for windowed usage totals
- `UsageLedger` — append-only usage event store; also a `UsageMeter` for event-summed usage

Features are associated with products via `ProductFeature`.

### Subscriptions

Located under `AlturaCode\Billing\Core\Subscriptions`:

- `Subscription` — the central aggregate describing a customer’s subscription
- `SubscriptionId` — identifier for a subscription
- `SubscriptionName` — logical name (for example, `default`, `primary`, `main`)
- `SubscriptionProvider` — which billing provider this subscription belongs to (for example, `stripe`)
- `SubscriptionItem` — line items inside a subscription (base plan, add-ons) 
- `SubscriptionItemEntitlement` - Entitlements granted by a subscription item
- `SubscriptionTrialPolicy` — trial behavior metadata, including whether a payment method is required up front and what happens if no payment method exists at trial end
- `SubscriptionTrialPaymentMethodCollection` — whether the trial requires a payment method before starting
- `SubscriptionTrialMissingPaymentMethodBehavior` — what to do when a trial ends without a payment method
- `SubscriptionRepository` — abstraction that you implement to persist subscriptions

Subscriptions are **provider-agnostic**; provider-specific IDs and state are managed by provider implementations and stored alongside subscriptions using `AlturaCode\Billing\Core\Provider\ExternalIdMapper`.

### Billing Providers

Located under `AlturaCode\Billing\Core\Provider`:

- `BillingProvider` — interface that concrete providers implement
- `BillingProviderRegistry` — registry mapping provider names (for example, `stripe`) to `BillingProvider` instances
- `BillingProviderResult`, `BillingProviderResultClientAction`, `BillingProviderResultClientActionType` — describe the outcome of provider operations.
- `SynchronousBillingProvider` - Basic billing provider implementation used for testing, demos, etc.

The **core** never directly calls Stripe or PayPal itself. Instead, it calls a `BillingProvider` implementation supplied by a provider package (for example, `billing-stripe`).

### BillingManager

`BillingManager` is a façade/orchestrator living in the root namespace `AlturaCode\Billing\Core`.

It coordinates:

- Loading products and prices via `ProductRepository`
- Creating and updating subscriptions via `SubscriptionRepository`
- Delegating to the proper `BillingProvider` via `BillingProviderRegistry`

It exposes high-level methods:

- `createSubscription(...)`
- `cancelSubscription(...)`
- `pauseSubscription(...)`
- `resumeSubscription(...)`

This is the main entry point most applications and framework adapters use.

### Entitlement Checking

The package provides a `UsageAwareEntitlementChecker` (created via `UsageAwareEntitlementCheckerFactory`) to check entitlements and inspect usage totals through a `UsageMeter`.

```php
use AlturaCode\Billing\Core\UsageAwareEntitlementCheckerFactory;
use AlturaCode\Billing\Core\EntitlementResolver;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;

$factory = new UsageAwareEntitlementCheckerFactory(
    new EntitlementResolver(),
    new DatabaseUsageMeter(), // Your implementation
    new UsageWindowCalculator()
);

$now = new \DateTimeImmutable();
$checker = $factory->create($subscription, $now);

// Check if a feature can be used
if ($checker->canUse('projects', 1)) {
    // ...
}

// Inspect usage totals for the current entitlement window
if ($checker->getUsedAmount('projects') > 0) {
    // ...
}
```

For simple checks without usage tracking, you can use the basic `EntitlementCheckerFactory`.

To record metered consumption, append positive `UsageEvent` instances through a `UsageLedger`. For resource quotas such as "active sites", implement a `UsageMeter` that derives current usage from your application state.

---

## Quick Start

Below is a minimal pure-PHP setup. In a real app, you would wire this via a container (for example, Laravel’s service container or Symfony’s DI).

### 1. Implement the Repositories

You must implement `ProductRepository`, `SubscriptionRepository`, and a `UsageMeter` or `UsageLedger`. A basic example might look like:

```php
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\Product;
use Symfony\Component\Uid\Ulid;

final class DatabaseProductRepository implements ProductRepository
{
    public function findByPriceId(ProductPriceId $priceId): ?Product
    {
        // Look up your product and price by $priceId->value() in the database
        // and hydrate a Product aggregate.
    }

    // Implement any other methods defined by ProductRepository...
}
```

`SubscriptionRepository` is responsible for persisting `Subscription` aggregates and querying them by ID or customer + name.

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;

final class DatabaseSubscriptionRepository implements SubscriptionRepository
{
    public function find(SubscriptionId $id): ?Subscription
    {
        // Query by $id->value() and hydrate Subscription
    }

    public function findForBillable(BillableIdentity $billable, SubscriptionName $name): ?Subscription
    {
        // Query by customer and name
    }

    public function save(Subscription $subscription): void
    {
        // Insert or update
    }
}
```

`UsageLedger` stores raw positive usage events independently of subscriptions and entitlements. Because it extends `UsageMeter`, it can also answer windowed usage queries by summing those events.

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageLedger;

final class DatabaseUsageLedger implements UsageLedger
{
    public function record(UsageEvent $event): bool
    {
        // Insert the raw event using $event->id()->value() as an idempotency key.
        // Return true if inserted, false if it already existed.
    }

    public function getUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window): int
    {
        // Return the total recorded amount for the billable and feature within the window.
    }
}
```

For resource quotas backed by application state, implement `UsageMeter` directly:

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageMeter;

final class ActiveSiteUsageMeter implements UsageMeter
{
    public function getUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window): int
    {
        // Return count of active sites for $billable from your application database.
    }
}
```

### 2. Register Billing Providers

Install a provider package (for example, Stripe) and register it in `BillingProviderRegistry`:

```php
use AlturaCode\Billing\Core\Provider\BillingProviderRegistry;
use AlturaCode\Billing\Stripe\StripeBillingProvider; // from alturacode/billing-stripe

$providerRegistry = new BillingProviderRegistry([
    'stripe' => new StripeBillingProvider(/* Stripe Client */),
]);
```

You can register multiple providers:

```php
$providerRegistry = new BillingProviderRegistry([
    'stripe' => $stripeProvider,
    'paypal' => $paypalProvider,
]);
```

The key (for example, `'stripe'`) is later passed as the `$provider` argument to `BillingManager` methods.

### 3. Create a Subscription

```php
use AlturaCode\Billing\Core\BillingManager;

$manager = new BillingManager($products, $subscriptions, $providerRegistry);

$result = $manager->createSubscription(
    new \AlturaCode\Billing\Core\SubscriptionDraft(
        name: 'default',                        // logical subscription name
        provider: 'stripe',                     // must exist in BillingProviderRegistry
        billableId: '123',                      // your internal customer identifier
        billableType: 'user',                   // your internal customer type 
        priceId: '01HZX3J8Y8B7MDQW9RGS0F7C39',  // the primary price ULID as a string
        quantity: 1,
        trialEndsAt: null,
        trialPolicy: null,
        addons: [
            ['priceId'   => '01HZX3J8Y8B7MDQW9RGS0F7C41', 'quantity'  => 5],
        ]
    ),
    providerOptions: [
        // Arbitrary provider-specific options forwarded to the BillingProvider
        // For example, Stripe metadata or trial configuration
    ],
);

if ($result->requiresAction()) {
    // 
}

$subscription = $result->subscription;
```

On success, `BillingProviderResult` holds the updated `Subscription` aggregate and additional metadata.

### 4. Cancel, Pause, and Resume

```php
// Cancel (optionally at the period end)
$result = $manager->cancelSubscription(
    subscriptionId: '01HZX3K6J9B8MDQW9RGS0F7D52',
    atPeriodEnd: true,
    providerOptions: [],
);

// Pause
$result = $manager->pauseSubscription(
    subscriptionId: '01HZX3K6J9B8MDQW9RGS0F7D52',
    providerOptions: [],
);

// Resume
$result = $manager->resumeSubscription(
    subscriptionId: '01HZX3K6J9B8MDQW9RGS0F7D52',
    providerOptions: [],
);
```

All these operations:

1. Load the `Subscription` from `SubscriptionRepository`.
2. Delegate the action to the configured `BillingProvider` for that subscription.
3. Persist the updated `Subscription` via `SubscriptionRepository`.

---

## Provider Results & Client Actions

Every call to a `BillingProvider` returns a `BillingProviderResult` that encapsulates:

- `status` — an instance of `BillingProviderResultStatus` (for example, Success, RequiresAction, Failed)
- `subscription` — the resulting `Subscription` aggregate after the provider call
- `clientAction` — optional `BillingProviderResultClientAction` for actions your frontend/client must perform

Client actions surface provider-specific flows like SCA/3D Secure, confirmation URLs, etc. A typical pattern:

```php
$result = $manager->createSubscription(...);

if ($result->requiresClientAction()) {
    $action = $result->clientAction;

    if ($action->type()->isRedirect()) {
        // Redirect the user to $action->url
    }
}
```

The exact semantics depend on the provider package, but `BillingProviderResult` gives you a uniform shape to work with.

---

## Mapping External IDs

`ExternalIdMapper` is a small helper for mapping between your internal IDs and provider-specific external IDs (for example, Stripe customer IDs, subscription IDs, etc.).

You should create an implementation of `ExternalIdMapper` which handles the saving and retrieval of external IDs.

`store()` and `storeMultiple()` are upserts: they create a mapping when none exists, or replace the external ID for the same internal ID. Mappings are one-to-one per type and provider, so implementations should throw `ExternalIdMappingConflictException` if the external ID already belongs to a different internal ID. Use `forget()` or `forgetMultiple()` to remove local mappings without deleting any upstream provider resource.

---

## Integrating with Frameworks

This core package purposefully does **not** know about any framework. To make it feel “first-class” in your environment, use or build an adapter.

### Laravel

A Laravel adapter typically provides:

- Service provider to register `BillingManager`, repositories, and providers
- Eloquent models implementing `ProductRepository` and `SubscriptionRepository`
- Facades/helpers for common subscription operations from controllers/blades

Example (hypothetical):

```php
// app/Providers/BillingServiceProvider.php

public function register(): void
{
    $this->app->singleton(BillingManager::class, function ($app) {
        return new BillingManager(
            products: $app->make(ProductRepository::class),
            subscriptions: $app->make(SubscriptionRepository::class),
            provider: $app->make(BillingProviderRegistry::class),
        );
    });
}
```

For non-Laravel frameworks, wire `BillingManager` into whatever DI or service container you use.

---

## Versioning & Stability

The package is currently in **early development** (`0.x` releases). Until `1.0.0`:

- APIs may change in minor versions
- Provider and framework adapter packages may evolve quickly

Please pin a specific version range in `composer.json` and check the changelog or release notes when upgrading.

---

## Contributing

Contributions are welcome. Typical areas where help is useful:

- New provider packages (for example, Braintree, Mollie, in-house gateways)
- New framework adapters (for example, Symfony, Slim)
- Better documentation and examples

To contribute:

1. Fork the repository.
2. Create a feature branch.
3. Add tests or examples where appropriate.
4. Open a pull request with a clear description and reasoning.

---

## License

This package is open-source software licensed under the **MIT license**.
