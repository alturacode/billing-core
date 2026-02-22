<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;
use LogicException;

final readonly class FeatureValue
{
    private function __construct(
        private FeatureKind     $kind,
        private bool|string|int $value,
        private ?UsagePolicy    $usagePolicy = null
    )
    {
        $this->assertValuePresent();
        $this->assertValueIsValidForKind();
        $this->assertUsagePolicyValidForKind();
    }

    public static function hydrate(mixed $data): self
    {
        $kind = FeatureKind::from($data['kind']);
        $usagePolicy = null;

        if (isset($data['usagePolicy'])) {
            $usagePolicy = UsagePolicy::hydrate($data['usagePolicy']);
        } elseif ($kind === FeatureKind::Limit) {
            // Default to calendar month for limits if no policy specified
            $usagePolicy = UsagePolicy::calendarMonth();
        }

        return new self(
            $kind,
            $data['value'],
            $usagePolicy
        );
    }

    public static function create(FeatureKind $kind, bool|string|int $value, ?UsagePolicy $usagePolicy = null): self
    {
        return new self($kind, $value, $usagePolicy);
    }

    public static function flagOn(): self
    {
        return new self(FeatureKind::Flag, true);
    }

    public static function flagOff(): self
    {
        return new self(FeatureKind::Flag, false);
    }

    public static function limit(int $value, ?UsagePolicy $usagePolicy = null): self
    {
        // Default to calendar month if no policy specified for limits
        $policy = $usagePolicy ?? UsagePolicy::calendarMonth();
        return new self(FeatureKind::Limit, $value, $policy);
    }

    public function value(): bool|string|int
    {
        return $this->value;
    }

    public function combine(self $other): self
    {
        if ($this->kind !== $other->kind) {
            throw new LogicException('Cannot combine FeatureValues with different kinds');
        }

        // For limits, ensure usage policies match
        if ($this->kind === FeatureKind::Limit) {
            if ($this->usagePolicy === null || $other->usagePolicy === null) {
                throw new LogicException('Cannot combine limit FeatureValues without usage policies');
            }
            if (!$this->usagePolicy->equals($other->usagePolicy)) {
                throw new LogicException('Cannot combine limit FeatureValues with different usage policies');
            }
        }

        // If values are the same, return the original value
        if ($this->value === $other->value) {
            return $this;
        }

        // If a flag, return the most permissive value
        if ($this->kind === FeatureKind::Flag) {
            return new self($this->kind, true);
        }

        // If a limit and one of the values is unlimited, return unlimited
        if ($this->kind === FeatureKind::Limit && $this->isUnlimited() || $other->isUnlimited()) {
            return new self($this->kind, 'unlimited', $this->usagePolicy);
        }

        // Otherwise, sum the values
        return new self($this->kind, $this->value + $other->value, $this->usagePolicy);
    }

    public function kind(): FeatureKind
    {
        return $this->kind;
    }

    public function usagePolicy(): ?UsagePolicy
    {
        return $this->usagePolicy;
    }

    public function isOn(): bool
    {
        return $this->value === true;
    }

    public function isOff(): bool
    {
        return $this->value === false;
    }

    public function isUnlimited(): bool
    {
        return $this->value === 'unlimited';
    }

    public function goesOverLimit($value): bool
    {
        return $this->isValueLimitThreshold() && $value > $this->value;
    }

    public function staysWithinLimit($value): bool
    {
        return $this->isValueLimitThreshold() && $value <= $this->value;
    }

    private function isValueLimitThreshold(): bool
    {
        return is_numeric($this->value) || $this->value === 'unlimited';
    }

    private function isValueBoolean(): bool
    {
        return is_bool($this->value);
    }

    private function assertValueIsValidForKind(): void
    {
        if ($this->kind === FeatureKind::Flag && $this->isValueBoolean() === false) {
            throw new LogicException('Flag features can only have boolean values.');
        }

        if ($this->kind === FeatureKind::Limit && $this->isValueLimitThreshold() === false) {
            throw new LogicException('Limit features can only have threshold values.');
        }
    }

    private function assertValuePresent(): void
    {
        if ($this->value === null || $this->value === '') {
            throw new InvalidArgumentException('Feature value cannot be null or empty');
        }

        if (is_numeric($this->value) && $this->value < 0) {
            throw new InvalidArgumentException('Feature value cannot be negative');
        }
    }

    private function assertUsagePolicyValidForKind(): void
    {
        // Flags should not have usage policies
        if ($this->kind === FeatureKind::Flag && $this->usagePolicy !== null) {
            throw new LogicException('Flag features cannot have usage policies');
        }
    }
}