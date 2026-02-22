<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

final readonly class UsagePolicy
{
    private function __construct(
        private UsagePeriod $period
    ) {
    }

    public static function hydrate(mixed $data): self
    {
        return new self(
            UsagePeriod::from($data['period'])
        );
    }

    public static function create(UsagePeriod $period): self
    {
        return new self($period);
    }

    public static function calendarMonth(): self
    {
        return new self(UsagePeriod::Month);
    }

    public static function perpetual(): self
    {
        return new self(UsagePeriod::Perpetual);
    }

    public function period(): UsagePeriod
    {
        return $this->period;
    }

    public function equals(self $other): bool
    {
        return $this->period === $other->period;
    }
}
