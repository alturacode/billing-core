<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

final readonly class SubscriptionTrialPolicy
{
    private function __construct(
        private ?SubscriptionTrialPaymentMethodCollection      $paymentMethodCollection,
        private ?SubscriptionTrialMissingPaymentMethodBehavior $missingPaymentMethodBehavior,
    ) {
    }

    public static function create(
        ?SubscriptionTrialPaymentMethodCollection $paymentMethodCollection = null,
        ?SubscriptionTrialMissingPaymentMethodBehavior $missingPaymentMethodBehavior = null,
    ): self {
        return new self($paymentMethodCollection, $missingPaymentMethodBehavior);
    }

    public static function hydrate(array $data): self
    {
        $paymentMethodCollection = null;
        if (array_key_exists('payment_method_collection', $data) && $data['payment_method_collection'] !== null) {
            $paymentMethodCollection = SubscriptionTrialPaymentMethodCollection::from((string) $data['payment_method_collection']);
        } elseif (array_key_exists('requires_payment_method_on_start', $data) && $data['requires_payment_method_on_start'] !== null) {
            $paymentMethodCollection = SubscriptionTrialPaymentMethodCollection::fromBool((bool) $data['requires_payment_method_on_start']);
        }

        $missingPaymentMethodBehavior = null;
        if (array_key_exists('missing_payment_method_behavior', $data) && $data['missing_payment_method_behavior'] !== null) {
            $missingPaymentMethodBehavior = SubscriptionTrialMissingPaymentMethodBehavior::from((string) $data['missing_payment_method_behavior']);
        }

        return new self($paymentMethodCollection, $missingPaymentMethodBehavior);
    }

    public function paymentMethodCollection(): ?SubscriptionTrialPaymentMethodCollection
    {
        return $this->paymentMethodCollection;
    }

    public function missingPaymentMethodBehavior(): ?SubscriptionTrialMissingPaymentMethodBehavior
    {
        return $this->missingPaymentMethodBehavior;
    }

    public function requiresPaymentMethodOnStart(): ?bool
    {
        return $this->paymentMethodCollection?->requiresPaymentMethod();
    }

    public function isEmpty(): bool
    {
        return $this->paymentMethodCollection === null && $this->missingPaymentMethodBehavior === null;
    }
}
