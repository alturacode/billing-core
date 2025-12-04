<?php

namespace AlturaCode\Billing\Core\Products;

enum ProductKind: string
{
    case Plan = 'plan';
    case AddOn = 'addon';
}
