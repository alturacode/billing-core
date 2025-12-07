<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionBillable;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItem;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionProvider;
use RuntimeException;

final class SubscriptionFactory
{
    /**
     * @param array<Product> $productList
     * @param SubscriptionDraft $draft
     * @return Subscription
     */
    public function fromProductListAndDraft(array $productList, SubscriptionDraft $draft): Subscription
    {
        $productPriceId = ProductPriceId::fromString($draft->priceId);
        $primaryProduct = $this->validatePlan($productList, $productPriceId, $draft);

        $primaryProductPrice = $primaryProduct->findPrice($productPriceId);
        $this->validateAddons($draft, $productList, $primaryProductPrice);

        $subscription = $this->makeSubscription($draft);
        $subscription = $this->addAddons($subscription, $productList, $draft);
        return $subscription->withPrimaryItem(SubscriptionItem::create(
            id: SubscriptionItemId::generate(),
            priceId: $productPriceId,
            quantity: $draft->quantity,
            price: $primaryProductPrice->price(),
        ));
    }

    /**
     * @param array<Product> $productList
     */
    private function validateAddons(
        SubscriptionDraft     $draft,
        array                 $productList,
        Products\ProductPrice $primaryPrice
    ): void
    {
        foreach ($draft->addons as $addon) {
            $addonPriceId = ProductPriceId::fromString($addon['priceId']);
            $product = array_find($productList, fn(Product $product) => $product->hasPrice($addonPriceId));
            if ($product === null) {
                throw new RuntimeException(sprintf('Product with price ID %s not found', $addon['priceId']));
            }

            // Ensure addon price currency is the same as primary price currency
            if ($product->findPrice($addonPriceId)->price()->currency()->equals($primaryPrice->price()->currency()) === false) {
                throw new RuntimeException(sprintf('Addon price currency must match primary price currency. Addon price ID: %s', $addon['priceId']));
            }
        }
    }

    private function makeSubscription(SubscriptionDraft $draft): Subscription
    {
        return Subscription::create(
            id: SubscriptionId::generate(),
            name: SubscriptionName::fromString($draft->name),
            billable: SubscriptionBillable::fromString($draft->billableType, $draft->billableId),
            provider: SubscriptionProvider::fromString($draft->provider),
            trialEndsAt: $draft->trialEndsAt
        );
    }

    /**
     * @param array<Product> $productList
     */
    private function addAddons(Subscription $subscription, array $productList, SubscriptionDraft $draft): Subscription
    {
        return $subscription->withItems(...array_map(function ($addon) use ($productList) {
            $addonPriceId = ProductPriceId::fromString($addon['priceId']);
            /** @var Product $product We are sure the product exists because it was validated in validateAddons() */
            $product = array_find($productList, fn(Product $product) => $product->hasPrice($addonPriceId));
            return SubscriptionItem::create(
                id: SubscriptionItemId::generate(),
                priceId: $addonPriceId,
                quantity: $addon['quantity'],
                price: $product->findPrice($addonPriceId)->price()
            );
        }, $draft->addons));
    }

    /**
     * @param array $productList
     * @param ProductPriceId $productPriceId
     * @param SubscriptionDraft $draft
     * @return Product
     */
    public function validatePlan(array $productList, ProductPriceId $productPriceId, SubscriptionDraft $draft): Product
    {
        /** @var Product $primaryProduct */
        $primaryProduct = array_find($productList, fn(Product $product) => $product->hasPrice($productPriceId));

        if ($primaryProduct === null) {
            throw new RuntimeException(sprintf('Product with price ID %s not found', $draft->priceId));
        }

        if ($primaryProduct->kind() !== ProductKind::Plan) {
            throw new RuntimeException('Primary product must be a plan.');
        }
        return $primaryProduct;
    }
}