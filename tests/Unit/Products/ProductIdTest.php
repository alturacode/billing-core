<?php

namespace Tests\Products;

use AlturaCode\Billing\Core\Products\ProductId;
use Symfony\Component\Uid\Ulid;

test('two ProductId instances with the same ULID are equal', function () {
    $ulid = new Ulid();
    $productId1 = ProductId::fromString($ulid->toString());
    $productId2 = ProductId::fromString($ulid->toString());

    expect($productId1->equals($productId2))->toBeTrue();
});

test('two ProductId instances with different ULIDs are not equal', function () {
    $productId1 = ProductId::fromString((string)new Ulid());
    $productId2 = ProductId::fromString((string)new Ulid());

    expect($productId1->equals($productId2))->toBeFalse();
});