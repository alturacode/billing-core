<?php
use AlturaCode\Billing\Core\Common\FeatureKind;
use AlturaCode\Billing\Core\Common\FeatureValue;

it('hydrates from array for flag and limit kinds', function () {
    $flag = FeatureValue::hydrate(['kind' => 'flag', 'value' => true]);
    $limit = FeatureValue::hydrate(['kind' => 'limit', 'value' => 10]);

    expect($flag->kind())->toBe(FeatureKind::Flag)
        ->and($flag->isOn())->toBeTrue()
        ->and($limit->kind())->toBe(FeatureKind::Limit)
        ->and($limit->value())->toBe(10);
});

it('supports unlimited limit through hydration', function () {
    $unlimited = FeatureValue::hydrate(['kind' => 'limit', 'value' => 'unlimited']);

    expect($unlimited->kind())->toBe(FeatureKind::Limit)
        ->and($unlimited->isUnlimited())->toBeTrue();
});

it('creates values via helpers', function () {
    $on = FeatureValue::flagOn();
    $off = FeatureValue::flagOff();
    $limit = FeatureValue::limit(5);

    expect($on->kind())->toBe(FeatureKind::Flag)
        ->and($on->isOn())->toBeTrue()
        ->and($off->isOff())->toBeTrue()
        ->and($limit->kind())->toBe(FeatureKind::Limit)
        ->and($limit->value())->toBe(5);
});

it('combines flag values correctly', function () {
    $off = FeatureValue::flagOff();
    $on = FeatureValue::flagOn();

    // off + off returns the original instance (still off)
    $r1 = $off->combine(FeatureValue::flagOff());
    expect($r1)->toBe($off)->and($r1->isOff())->toBeTrue();

    // on + on returns the original instance (still on)
    $r2 = $on->combine(FeatureValue::flagOn());
    expect($r2)->toBe($on)->and($r2->isOn())->toBeTrue();

    // off + on (or on + off) -> true
    $r3 = $off->combine($on);
    expect($r3->isOn())->toBeTrue();
});

it('combines limit values by summing and handling unlimited', function () {
    $a = FeatureValue::limit(5);
    $b = FeatureValue::limit(10);
    $sum = $a->combine($b);
    expect($sum->kind())->toBe(FeatureKind::Limit)
        ->and($sum->value())->toBe(15);

    $unlimited = FeatureValue::hydrate(['kind' => 'limit', 'value' => 'unlimited']);
    $res1 = $a->combine($unlimited);
    $res2 = $unlimited->combine($b);
    expect($res1->isUnlimited())->toBeTrue()
        ->and($res2->isUnlimited())->toBeTrue();
});

it('throws when combining different kinds', function () {
    $flag = FeatureValue::flagOn();
    $limit = FeatureValue::limit(3);
    $flag->combine($limit);
})->throws(LogicException::class);

it('validates presence and non-negative values', function () {
    FeatureValue::create(FeatureKind::Flag, '');
})->throws(InvalidArgumentException::class);

it('rejects negative numeric values', function () {
    FeatureValue::create(FeatureKind::Limit, -1);
})->throws(InvalidArgumentException::class);

it('enforces type per kind', function () {
    // flag must be boolean
    FeatureValue::create(FeatureKind::Flag, 'yes');
})->throws(LogicException::class);

it('enforces numeric/unlimited for limit kind', function () {
    FeatureValue::create(FeatureKind::Limit, 'ten');
})->throws(LogicException::class);

it('evaluates within and over limit correctly', function () {
    $limit = FeatureValue::limit(10);

    expect($limit->staysWithinLimit(10))->toBeTrue()
        ->and($limit->staysWithinLimit(11))->toBeFalse()
        ->and($limit->goesOverLimit(10))->toBeFalse()
        ->and($limit->goesOverLimit(11))->toBeTrue();
});
