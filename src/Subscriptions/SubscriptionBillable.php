<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

use InvalidArgumentException;
use Stringable;

final readonly class SubscriptionBillable
{
    private function __construct(private string $type, private mixed $id = null)
    {
        if ($type === '') {
            throw new InvalidArgumentException('Subscription billable type cannot be empty');
        }

        if ($id === null) {
            throw new InvalidArgumentException('Subscription billable id cannot be null');
        }

        if (!is_string($this->id) && !is_int($this->id)) {
            throw new InvalidArgumentException('Subscription billable id should be a string or integer');
        }
    }

    public static function hydrate(mixed $data): self
    {
        return new self($data['type'], $data['id']);
    }

    public static function fromString(string $type, mixed $id): SubscriptionBillable
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