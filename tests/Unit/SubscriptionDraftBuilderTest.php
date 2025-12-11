<?php

use AlturaCode\Billing\Core\SubscriptionDraft;
use AlturaCode\Billing\Core\SubscriptionDraftBuilder;
use AlturaCode\Billing\Core\UnableToCreateSubscriptionDraftException;

it('builds a subscription draft', function () {
    $builder = new SubscriptionDraftBuilder();

    $draft = $builder
        ->withName('default')
        ->withBillable('user', 'user_1')
        ->withProvider('stripe')
        ->withPlanPriceId('price_123')
        ->withTrialEndsAt(new DateTimeImmutable('2021-01-01 00:00:00'))
        ->withAddon('addon_123')
        ->build();

    expect($draft)->toBeInstanceOf(SubscriptionDraft::class)
        ->and($draft->name)->toBe('default')
        ->and($draft->provider)->toBe('stripe')
        ->and($draft->priceId)->toBe('price_123')
        ->and($draft->quantity)->toBe(1)
        ->and($draft->trialEndsAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($draft->addons)->toHaveCount(1)
        ->and($draft->addons[0]['priceId'])->toBe('addon_123')
        ->and($draft->addons[0]['quantity'])->toBe(1);
});

it('builds a subscription with custom quantities and trial days', function () {
    $builder = new SubscriptionDraftBuilder();

    /** @noinspection PhpUnhandledExceptionInspection */
    $draft = $builder
        ->withName('default')
        ->withBillable('user', 123)
        ->withProvider('stripe')
        ->withPlanPriceId('price_123', 2)
        ->withAddon('addon_123', 2)
        ->withTrialDays(3)
        ->build();

    expect($draft)->toBeInstanceOf(SubscriptionDraft::class)
        ->and($draft->name)->toBe('default')
        ->and($draft->provider)->toBe('stripe')
        ->and($draft->priceId)->toBe('price_123')
        ->and($draft->quantity)->toBe(2)
        ->and($draft->trialEndsAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($draft->trialEndsAt)->diff(new DateTimeImmutable())->days->toBe(3)
        ->and($draft->addons)->toHaveCount(1)
        ->and($draft->addons[0]['priceId'])->toBe('addon_123')
        ->and($draft->addons[0]['quantity'])->toBe(2);
});

it('builds a draft using plan slug and interval information', function () {
    $builder = new SubscriptionDraftBuilder();

    $draft = $builder
        ->withName('default')
        ->withBillable('user', 'user_1')
        ->withProvider('stripe')
        ->withPlan('free', 'monthly', 1, 'usd')
        ->build();

    expect($draft)->toBeInstanceOf(SubscriptionDraft::class)
        ->and($draft->priceId)->toBeNull()
        ->and($draft->plan)->toBe('free')
        ->and($draft->intervalType)->toBe('monthly')
        ->and($draft->intervalCount)->toBe(1)
        ->and($draft->currency)->toBe('usd');
});

it('throws exception if any required property is missing', function () {
    $builder = new SubscriptionDraftBuilder();

    expect(fn() => $builder->build())
        ->toThrow(UnableToCreateSubscriptionDraftException::class, "Missing required property 'name'")
        ->and(fn() => $builder->withName('test')->build())
        ->toThrow(UnableToCreateSubscriptionDraftException::class, "Missing required property 'billableId'")
        ->and(fn() => $builder->withName('test')->withBillable('user', '')->build())
        ->toThrow(UnableToCreateSubscriptionDraftException::class, "Missing required property 'billableId'")
        ->and(fn() => $builder->withName('test')->withBillable('user', 'user_1')->withPlanPriceId('price_1')->build())
        ->toThrow(UnableToCreateSubscriptionDraftException::class, "Missing required property 'provider'");
});

it('throws exception if plan price identifier is missing', function () {
    $builder = new SubscriptionDraftBuilder();

    expect(fn() => $builder->withName('test')->withBillable('user', 'user_1')->withProvider('stripe')->build())
        ->toThrow(UnableToCreateSubscriptionDraftException::class, "Missing plan price identifier. You must provide either a plan price id or plan slug with currency and interval information.");
});