<?php

declare(strict_types=1);

namespace Tests\Fixtures\Common;

use AlturaCode\Billing\Core\Common\BillableIdentity;

final class BillableIdentityMother
{
    public static function create(string $type = 'user', int|string $id = 'user-1'): BillableIdentity
    {
        return BillableIdentity::fromString($type, $id);
    }
}
