<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductSlug;
use AlturaCode\Billing\Core\Products\ProductKind;

it('ProductId can be generated, hydrated and check equality', function () {
    $id = ProductId::generate();
    $idStr = $id->value();
    
    expect(ProductId::hydrate($idStr)->value())->toBe($idStr)
        ->and(ProductId::fromString($idStr)->value())->toBe($id->value())
        ->and((string) $id)->toBe($idStr);
});

it('ProductPriceId can be generated, hydrated and check equality', function () {
    $id = ProductPriceId::generate();
    $idStr = $id->value();
    
    expect(ProductPriceId::hydrate($idStr)->value())->toBe($idStr)
        ->and(ProductPriceId::fromString($idStr)->equals($id))->toBeTrue()
        ->and((string) $id)->toBe($idStr);
});

it('ProductSlug can be hydrated and check equality', function () {
    $slug = ProductSlug::fromString('basic_plan');
    expect(ProductSlug::hydrate('basic_plan')->value())->toBe('basic_plan')
        ->and(ProductSlug::fromString('basic_plan')->equals($slug))->toBeTrue()
        ->and((string) $slug)->toBe('basic_plan');
});

it('ProductSlug validates format', function () {
    ProductSlug::fromString('Basic-Plan');
})->throws(InvalidArgumentException::class, 'Plan slug should only contain lowercase letters, numbers and underscores');

it('ProductSlug validates it is not empty', function () {
    ProductSlug::fromString('');
})->throws(InvalidArgumentException::class, 'Plan slug cannot be empty');

it('ProductKind has expected cases', function () {
    expect(ProductKind::Plan->value)->toBe('plan')
        ->and(ProductKind::AddOn->value)->toBe('addon');
});
