<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Subscriptions\Subscription;

final readonly class BillingProviderResult
{
    private function __construct(
        public Subscription                      $subscription,
        public BillingProviderResultClientAction $clientAction,
    )
    {
    }

    public static function completed(Subscription $subscription): self
    {
        return new self(
            $subscription,
            BillingProviderResultClientAction::none(),
        );
    }

    public static function redirect(Subscription $subscription, string $url): self
    {
        return new self(
            $subscription,
            BillingProviderResultClientAction::redirect($url),
        );
    }

    public function requiresAction(): bool
    {
        return $this->clientAction->type->isNone() === false;
    }
}