<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use DateTimeImmutable;

final readonly class UsageWindow
{
    private function __construct(
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt
    ) {
    }

    public static function create(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): self
    {
        return new self($startsAt, $endsAt);
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function equals(self $other): bool
    {
        return $this->startsAt == $other->startsAt
            && $this->endsAt == $other->endsAt;
    }
}
