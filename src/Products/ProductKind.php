<?php

namespace AlturaCode\Billing\Core;

enum ProductKind: string
{
    case Plan = 'plan';
    case AddOn = 'addon';
}
