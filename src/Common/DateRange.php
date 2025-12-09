<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DateRange
{
    private function __construct(
        private ?DateTimeImmutable $start,
        private ?DateTimeImmutable $end
    )
    {
        if ($this->start !== null && $this->end !== null && $this->start > $this->end) {
            throw new InvalidArgumentException('Start date cannot be after end date');
        }
        
        // At least one date must be present
        if ($this->start === null && $this->end === null) {
            throw new InvalidArgumentException('At least one date must be present');
        }
    }
    
    public static function hydrate(array $data): self
    {
        return new self(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['start']),
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['end'])
        );
    }
    
    public function start(): ?DateTimeImmutable
    {
        return $this->start;
    }
    
    public function end(): ?DateTimeImmutable
    {
        return $this->end;
    }
    
    public function isInRange(DateTimeImmutable $date): bool
    {
        return ($this->start === null || $date >= $this->start) && ($this->end === null || $date <= $this->end);
    }
}