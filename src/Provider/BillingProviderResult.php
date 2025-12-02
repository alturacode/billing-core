<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;

final readonly class BillingProviderResult
{
    private function __construct(
        public Subscription                      $subscription,
        public BillingProviderResultStatus       $status,
        public BillingProviderResultClientAction $clientAction,
        public ?string                           $reason
    )
    {
    }

    public static function completed(Subscription $subscription): self
    {
        return new self(
            $subscription,
            BillingProviderResultStatus::Success,
            BillingProviderResultClientAction::none(),
            null
        );
    }

    public static function failed(Subscription $subscription, string $reason): self
    {
        return new self(
            $subscription,
            BillingProviderResultStatus::Failure,
            BillingProviderResultClientAction::none(),
            $reason
        );
    }

    public static function redirect(Subscription $subscription, string $url): self
    {
        return new self(
            $subscription,
            BillingProviderResultStatus::RequiresAction,
            BillingProviderResultClientAction::redirect($url),
            null
        );
    }

    public function requiresAction(): bool
    {
        return $this->status === BillingProviderResultStatus::RequiresAction;
    }
}