<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use DateInterval;
use DateMalformedStringException;
use DateTimeImmutable;

final class SubscriptionDraftBuilder
{
    private string $name;
    private mixed $billableId;
    private string $billableType;
    private ?string $priceId = null;
    private ?string $plan = null;
    private ?string $intervalType = null;
    private int $intervalCount = 1;
    private ?string $currency = null;
    private string $provider;
    private int $quantity = 1;
    private ?DateTimeImmutable $trialEndsAt = null;
    private array $addons = [];

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withBillable(string $billableType, mixed $billableId): self
    {
        $this->billableType = $billableType;
        $this->billableId = $billableId;
        return $this;
    }

    public function withProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function withPlanPriceId(string $priceId, int $quantity = 1): self
    {
        $this->priceId = $priceId;
        $this->quantity = $quantity;
        return $this;
    }

    public function withPlan(string $plan, string $intervalType, int $intervalCount, string $currency): self
    {
        $this->plan = $plan;
        $this->intervalType = $intervalType;
        $this->intervalCount = $intervalCount;
        $this->currency = $currency;
        return $this;
    }

    public function withTrialEndsAt(?DateTimeImmutable $trialEndsAt): self
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function withTrialDays(int $trialDays): self
    {
        $this->trialEndsAt = new DateTimeImmutable()->modify('tomorrow')->setTime(0, 0)->add(new DateInterval("P{$trialDays}D"));
        return $this;
    }

    public function withAddon(string $priceId, int $quantity = 1): self
    {
        $this->addons[] = ['priceId' => $priceId, 'quantity' => $quantity];
        return $this;
    }

    public function build(): SubscriptionDraft
    {
        $this->validate();

        return new SubscriptionDraft(
            name: $this->name,
            billableId: $this->billableId,
            billableType: $this->billableType,
            provider: $this->provider,
            quantity: $this->quantity,
            plan: $this->plan,
            priceId: $this->priceId,
            intervalType: $this->intervalType,
            intervalCount: $this->intervalCount,
            currency: $this->currency,
            trialEndsAt: $this->trialEndsAt,
            addons: $this->addons
        );
    }

    private function validate(): void
    {
        $required = ['name', 'billableId', 'billableType', 'provider'];
        foreach ($required as $property) {
            if (empty($this->{$property})) {
                throw UnableToCreateSubscriptionDraftException::missingRequiredProperty($property);
            }
        }

        if (empty($this->priceId) && (empty($this->plan) || empty($this->intervalType) || empty($this->intervalCount) || empty($this->currency))) {
            throw UnableToCreateSubscriptionDraftException::missingPlanPriceIdentifier();
        }
    }
}