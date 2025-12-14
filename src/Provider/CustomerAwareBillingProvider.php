<?php

namespace AlturaCode\Billing\Core\Provider;

use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\Common\BillableIdentity;

interface CustomerAwareBillingProvider extends BillingProvider
{
    public function syncCustomer(
        BillableIdentity $billable,
        ?BillableDetails $details = null,
        array            $options = [],
    ): CustomerSyncResult;
}