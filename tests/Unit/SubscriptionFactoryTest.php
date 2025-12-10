<?php

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureValue;
use AlturaCode\Billing\Core\Common\Money;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductFeature;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPrice;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductSlug;
use AlturaCode\Billing\Core\SubscriptionDraft;
use AlturaCode\Billing\Core\SubscriptionFactory;

it('creates a subscription with addons and features', function () {
    $plan = Product::create(
        id: ProductId::generate(),
        kind: ProductKind::Plan,
        slug: ProductSlug::fromString('plan'),
        name: 'Plan',
        description: 'Plan description',
    )->withPrices(
        ProductPrice::monthly(ProductPriceId::generate(), Money::usd(100))
    )->withFeatures(
        ProductFeature::create(FeatureKey::fromString('feature_a'), FeatureValue::flagOn())
    );

    $addon = Product::create(
        id: ProductId::generate(),
        kind: ProductKind::AddOn,
        slug: ProductSlug::fromString('addon'),
        name: 'Addon',
        description: 'Addon description',
    )->withPrices(
        ProductPrice::monthly(ProductPriceId::generate(), Money::usd(50))
    )->withFeatures(
        ProductFeature::create(FeatureKey::fromString('feature_b'), FeatureValue::flagOn())
    );

    $factory = new SubscriptionFactory();
    $subscription = $factory->fromProductListAndDraft([$plan, $addon], new SubscriptionDraft(
        name: 'default',
        billableId: 'user_1',
        billableType: 'user',
        priceId: $plan->prices()[0]->id()->value(),
        provider: 'stripe',
        addons: [
            ['priceId' => $addon->prices()[0]->id()->value(), 'quantity' => 1]
        ],
    ));

    expect($subscription->name()->value())->toBe('default')
        ->and($subscription->billable()->id())->toBe('user_1')
        ->and($subscription->billable()->type())->toBe('user');
});