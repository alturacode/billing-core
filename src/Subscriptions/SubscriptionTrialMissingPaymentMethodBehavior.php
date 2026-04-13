<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Subscriptions;

enum SubscriptionTrialMissingPaymentMethodBehavior: string
{
    case Cancel = 'cancel';
    case Pause = 'pause';
    case CreateInvoice = 'create_invoice';

    public function isCancel(): bool
    {
        return $this === self::Cancel;
    }

    public function isPause(): bool
    {
        return $this === self::Pause;
    }

    public function isCreateInvoice(): bool
    {
        return $this === self::CreateInvoice;
    }
}
