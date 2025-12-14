<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

enum CustomerSyncResultStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
}