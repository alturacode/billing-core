<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

interface BillingProviderRegistry
{
    public function get(string $provider): BillingProvider;

    /**
     * @return BillingProvider[]
     */
    public function all(): array;

    /**
     * @return ProductAwareBillingProvider[]
     */
    public function productAwareProviders(): array;
}