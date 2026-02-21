<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\DateRange;

it('can be hydrated', function () {
    $startStr = '2023-01-01 00:00:00';
    $endStr = '2023-12-31 23:59:59';
    $range = DateRange::hydrate(['start' => $startStr, 'end' => $endStr]);

    expect($range->start()->format('Y-m-d H:i:s'))->toBe($startStr)
        ->and($range->end()->format('Y-m-d H:i:s'))->toBe($endStr);
});

it('validates start is not after end', function () {
    $start = new DateTimeImmutable('2023-12-31');
    $end = new DateTimeImmutable('2023-01-01');
    
    // Using reflection to call private constructor or just adding a static creator if I could, 
    // but hydrate is the only public way to create it currently in the code? 
    // Wait, the constructor is private. hydrate uses 'new self'. 
    // I should probably add a public static creator to DateRange if it's missing, but I should stay consistent with the codebase.
    // If I cannot use 'from' or similar, I must use hydrate or similar.
    
    DateRange::hydrate(['start' => '2023-12-31 00:00:00', 'end' => '2023-01-01 00:00:00']);
})->throws(InvalidArgumentException::class, 'Start date cannot be after end date');

it('validates at least one date is present', function () {
    DateRange::from(null, null);
})->throws(InvalidArgumentException::class, 'At least one date must be present');

it('can be created with only start date', function () {
    $start = new DateTimeImmutable('2023-01-01');
    $range = DateRange::from(start: $start);
    
    expect($range->start())->toBe($start)
        ->and($range->end())->toBeNull()
        ->and($range->isInRange(new DateTimeImmutable('2023-01-01')))->toBeTrue()
        ->and($range->isInRange(new DateTimeImmutable('2022-12-31')))->toBeFalse();
});

it('can be created with only end date', function () {
    $end = new DateTimeImmutable('2023-12-31');
    $range = DateRange::from(end: $end);
    
    expect($range->start())->toBeNull()
        ->and($range->end())->toBe($end)
        ->and($range->isInRange(new DateTimeImmutable('2023-12-31')))->toBeTrue()
        ->and($range->isInRange(new DateTimeImmutable('2024-01-01')))->toBeFalse();
});

it('checks if date is in range', function () {
    $range = DateRange::hydrate(['start' => '2023-01-01 00:00:00', 'end' => '2023-12-31 23:59:59']);
    
    expect($range->isInRange(new DateTimeImmutable('2023-06-01')))->toBeTrue()
        ->and($range->isInRange(new DateTimeImmutable('2022-12-31')))->toBeFalse()
        ->and($range->isInRange(new DateTimeImmutable('2024-01-01')))->toBeFalse();
});
