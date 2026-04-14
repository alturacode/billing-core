<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Features;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class UsageEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        private UsageEventId $id,
        private BillableIdentity $billable,
        private FeatureKey $featureKey,
        private int $amount,
        private DateTimeImmutable $recordedAt,
        private array $metadata = [],
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Usage event amount must be positive.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function create(
        UsageEventId $id,
        BillableIdentity $billable,
        FeatureKey $featureKey,
        int $amount,
        ?DateTimeImmutable $recordedAt = null,
        array $metadata = [],
    ): self {
        $recordedAt = ($recordedAt ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'));

        return new self($id, $billable, $featureKey, $amount, $recordedAt, $metadata);
    }

    /**
     * @param array{
     *     id: mixed,
     *     billable: array{type: mixed, id: mixed},
     *     feature_key: mixed,
     *     amount: mixed,
     *     recorded_at: mixed,
     *     metadata?: array<string, mixed>
     * } $data
     */
    public static function hydrate(mixed $data): self
    {
        $recordedAt = new DateTimeImmutable((string) $data['recorded_at']);
        $recordedAt = $recordedAt->setTimezone(new DateTimeZone('UTC'));

        return self::create(
            UsageEventId::hydrate($data['id']),
            BillableIdentity::hydrate($data['billable']),
            FeatureKey::hydrate($data['feature_key']),
            (int) $data['amount'],
            $recordedAt,
            $data['metadata'] ?? [],
        );
    }

    public function id(): UsageEventId
    {
        return $this->id;
    }

    public function billable(): BillableIdentity
    {
        return $this->billable;
    }

    public function featureKey(): FeatureKey
    {
        return $this->featureKey;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
