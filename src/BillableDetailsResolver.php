<?php

namespace AlturaCode\Billing\Core;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\BillableDetails;

interface BillableDetailsResolver
{
    public function resolve(BillableIdentity $billable): ?BillableDetails;
}