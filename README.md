# AlturaCode Billing Core

Framework-agnostic billing engine for PHP apps. Designed for multiple billing providers and framework adapters.

This package contains the **core billing domain and orchestration logic**:

- Products, prices, intervals, and features
- Subscriptions and subscription items
- Provider-agnostic subscription workflows (create, cancel, pause, resume)
- A pluggable provider registry so you can swap Stripe, PayPal, or any other gateway

Framework-specific integrations (for example, Laravel) and concrete billing providers (for example, Stripe) live in **separate packages**, such as:

- `alturacode/billing-stripe` — Stripe provider implementation
- `alturacode/billing-laravel` — Laravel adapter

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

And, if you want framework helpers, install a **framework adapter** (for example, Laravel):

```bash
composer require alturacode/billing-laravel
```

---

## Core Concepts

The core package exposes a small set of domain concepts. The namespaces below are simplified; look in `src/` for full details.

### Money & Currency

- `AlturaCode\Billing\Core\Money`
- `AlturaCode\Billing\Core\Currency`

These model monetary values and currencies. Prices on products are expressed with these types.

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
- `FeatureKey` — identifier for a feature
- `FeatureKind` — describes what kind of feature it is (boolean, quota, etc.)

Features are typically associated with products or prices via `ProductFeature`.

### Subscriptions

Located under `AlturaCode\Billing\Core\Subscriptions`:

- `Subscription` — the central aggregate describing a customer’s subscription
- `SubscriptionId` — identifier for a subscription
- `SubscriptionName` — logical name (for example, `default`, `primary`, `main`)
- `SubscriptionBillable` — polymorphic-style identifier of your customer in your own system
- `SubscriptionItem` / `SubscriptionItemId` — line items inside a subscription (base plan, add-ons)
- `SubscriptionStatus` — status (for example, Active, Paused, Canceled, Incomplete)
- `SubscriptionProvider` — which billing provider this subscription belongs to (for example, `stripe`)
- `SubscriptionRepository` — abstraction that you implement to persist subscriptions

Subscriptions are **provider-agnostic**; provider-specific IDs and state are managed by provider implementations and stored alongside subscriptions using your `SubscriptionRepository`.

### Billing Providers

Located under `AlturaCode\Billing\Core\Provider`:

- `BillingProvider` — interface that concrete providers implement
- `BillingProviderRegistry` — registry mapping provider names (for example, `stripe`) to `BillingProvider` instances
- `BillingProviderResult`, `BillingProviderResultClientAction`, `BillingProviderResultClientActionType` — describe the outcome of provider operations

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

---

## Quick Start

Below is a minimal pure-PHP setup. In a real app, you would wire this via a container (for example, Laravel’s service container or Symfony’s DI).

### 1. Implement the Repositories

You must implement `ProductRepository` and `SubscriptionRepository`. A basic example might look like:

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
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionBillable;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;

final class DatabaseSubscriptionRepository implements SubscriptionRepository
{
    public function find(SubscriptionId $id): ?Subscription
    {
        // Query by $id->value() and hydrate Subscription
    }

    public function findForBillable(SubscriptionBillable $billable, SubscriptionName $name): ?Subscription
    {
        // Query by customer and name
    }

    public function save(Subscription $subscription): void
    {
        // Insert or update
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
