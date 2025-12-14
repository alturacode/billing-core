<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Common;

use InvalidArgumentException;

final readonly class BillableIdentity
{
    private function __construct(private string $type, private mixed $id = null)
    {
        if ($type === '') {
            throw new InvalidArgumentException('Billable type cannot be empty');
        }

        if ($id === null) {
            throw new InvalidArgumentException('Billable id cannot be null');
        }

        if (!is_string($this->id) && !is_int($this->id)) {
            throw new InvalidArgumentException('Billable id should be a string or integer');
        }
    }

    public static function hydrate(mixed $data): self
    {
        return new self($data['type'], $data['id']);
    }

    public static function fromString(string $type, mixed $id): BillableIdentity
    {
        return new self($type, $id);
    }

    public function type(): string
    {
        return $this->type;
    }
    
    public function id(): string|int
    {
        return $this->id;
    }
}