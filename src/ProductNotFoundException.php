<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Product not found')
    {
        parent::__construct($message);
    }
}