<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\UsageWindowCalculator;
use AlturaCode\Billing\Core\Features\UsageRepository;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use DateTimeImmutable;

final readonly class UsageAwareEntitlementChecker
{
    /**
     * @param array<string, EffectiveEntitlement> $effectiveEntitlements
     */
    public function __construct(
        private array                  $effectiveEntitlements,
        private UsageRepository        $usageRepository,
        private UsageWindowCalculator  $windowCalculator,
        private SubscriptionId         $subscriptionId,
    ) {
    }

    /**
     * Atomically attempt to consume a specific amount of a feature.
     *
     * For flags: returns the flag state (on/off), no usage tracking occurs.
     * For limits: attempts to atomically consume the amount and returns true if successful.
     *
     * This is the authoritative method for enforcing usage limits. Always prefer this
     * over canUse() when performing an action that consumes a resource.
     *
     * @param string $keyName The feature key name
     * @param int $amount The amount to consume (default: 1)
     * @param DateTimeImmutable|null $at The time to check (default: now in UTC)
     * @return bool True if consumption succeeded or feature is allowed, false otherwise
     */
    public function tryConsume(string $keyName, int $amount = 1, ?DateTimeImmutable $at = null): bool
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return false;
        }

        $value = $effectiveEntitlement->value();

        return match ($value->kind()) {
            FeatureKind::Flag => $value->isOn(),
            FeatureKind::Limit => $this->tryConsumeLimit($key, $value, $amount, $at),
        };
    }

    /**
     * Check if a feature can be used without consuming it.
     *
     * For flags: returns the flag state (on/off).
     * For limits: performs a non-atomic check of current usage vs. limit.
     *
     * IMPORTANT: This method is NOT atomic. For limit features, there is a race
     * condition between checking and consuming. Always prefer tryConsume() when
     * performing the actual action.
     *
     * @param string $keyName The feature key name
     * @param int $newAmount The amount to check (default: 1)
     * @param DateTimeImmutable|null $at The time to check (default: now in UTC)
     * @return bool True if feature is allowed, false otherwise
     */
    public function canUse(string $keyName, int $newAmount = 1, ?DateTimeImmutable $at = null): bool
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return false;
        }

        $value = $effectiveEntitlement->value();

        return match ($value->kind()) {
            FeatureKind::Flag => $value->isOn(),
            FeatureKind::Limit => $this->canUseLimit($key, $value, $newAmount, $at),
        };
    }

    /**
     * Get the current used amount for a limit feature.
     *
     * @param string $keyName The feature key name
     * @param DateTimeImmutable|null $at The time to check (default: now in UTC)
     * @return int The amount currently used in the current window (0 if not a limit feature)
     */
    public function getUsedAmount(string $keyName, ?DateTimeImmutable $at = null): int
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return 0;
        }

        $value = $effectiveEntitlement->value();

        if ($value->kind() !== FeatureKind::Limit) {
            return 0;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return 0;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);

        return $this->usageRepository->getUsedAmount(
            $this->subscriptionId,
            $key,
            $window
        );
    }

    /**
     * Set the used amount directly for a perpetual limit feature.
     *
     * This is useful when you want to set an exact count (e.g., after recounting resources).
     * Only works with perpetual limits.
     *
     * @param string $keyName The feature key name
     * @param int $amount The amount to set (must be non-negative)
     * @param DateTimeImmutable|null $at The time reference (default: now in UTC)
     * @return void
     */
    public function setUsedAmount(string $keyName, int $amount, ?DateTimeImmutable $at = null): void
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return;
        }

        $value = $effectiveEntitlement->value();

        if ($value->kind() !== FeatureKind::Limit) {
            return;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);

        $this->usageRepository->setUsedAmount(
            $this->subscriptionId,
            $key,
            $window,
            $amount
        );
    }

    /**
     * Increment the usage for a perpetual limit feature.
     *
     * This is useful when creating a resource (e.g., creating a website increments the counter).
     * Works with any limit type, but especially useful for perpetual limits.
     *
     * @param string $keyName The feature key name
     * @param int $amount The amount to increment by (must be positive, default: 1)
     * @param DateTimeImmutable|null $at The time reference (default: now in UTC)
     * @return void
     */
    public function incrementUsage(string $keyName, int $amount = 1, ?DateTimeImmutable $at = null): void
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return;
        }

        $value = $effectiveEntitlement->value();

        if ($value->kind() !== FeatureKind::Limit) {
            return;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);

        $this->usageRepository->incrementUsage(
            $this->subscriptionId,
            $key,
            $window,
            $amount
        );
    }

    /**
     * Decrement the usage for a perpetual limit feature.
     *
     * This is useful when deleting a resource (e.g., deleting a website decrements the counter).
     * Works with any limit type, but especially useful for perpetual limits.
     *
     * @param string $keyName The feature key name
     * @param int $amount The amount to decrement by (must be positive, default: 1)
     * @param DateTimeImmutable|null $at The time reference (default: now in UTC)
     * @return void
     */
    public function decrementUsage(string $keyName, int $amount = 1, ?DateTimeImmutable $at = null): void
    {
        $at = $at ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $key = FeatureKey::fromString($keyName);
        $effectiveEntitlement = $this->effectiveEntitlements[$key->value()] ?? null;

        if ($effectiveEntitlement === null) {
            return;
        }

        $value = $effectiveEntitlement->value();

        if ($value->kind() !== FeatureKind::Limit) {
            return;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            return;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);

        $this->usageRepository->decrementUsage(
            $this->subscriptionId,
            $key,
            $window,
            $amount
        );
    }

    private function tryConsumeLimit(
        FeatureKey $key,
        \AlturaCode\Billing\Core\Common\FeatureValue $value,
        int $amount,
        DateTimeImmutable $at
    ): bool {
        // Unlimited limits always succeed
        if ($value->isUnlimited()) {
            return true;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            // If no usage policy (shouldn't happen with proper construction), deny
            return false;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);
        $limit = (int) $value->value();

        return $this->usageRepository->tryConsume(
            $this->subscriptionId,
            $key,
            $window,
            $amount,
            $limit
        );
    }

    private function canUseLimit(
        FeatureKey $key,
        \AlturaCode\Billing\Core\Common\FeatureValue $value,
        int $newAmount,
        DateTimeImmutable $at
    ): bool {
        // Unlimited limits always succeed
        if ($value->isUnlimited()) {
            return true;
        }

        $usagePolicy = $value->usagePolicy();
        if ($usagePolicy === null) {
            // If no usage policy (shouldn't happen with proper construction), deny
            return false;
        }

        $window = $this->windowCalculator->forPolicyAt($usagePolicy, $at);
        $currentUsage = $this->usageRepository->getUsedAmount(
            $this->subscriptionId,
            $key,
            $window
        );

        return $value->staysWithinLimit($currentUsage + $newAmount);
    }
}
