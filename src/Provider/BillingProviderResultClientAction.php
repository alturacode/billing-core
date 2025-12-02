<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

final readonly class BillingProviderResultClientAction
{
    private function __construct(
        public BillingProviderResultClientActionType $clientActionType,
        public ?string                               $url
    )
    {
    }

    public static function redirect(string $url): self
    {
        return new self(BillingProviderResultClientActionType::Redirect, $url);
    }

    public static function none(): self
    {
        return new self(BillingProviderResultClientActionType::None, null);
    }
}