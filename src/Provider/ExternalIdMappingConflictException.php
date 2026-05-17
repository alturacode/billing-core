<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Core\Provider;

use RuntimeException;

final class ExternalIdMappingConflictException extends RuntimeException
{
    public static function forExternalId(
        string $type,
        string $provider,
        string|int $externalId,
        string|int $existingInternalId,
        string|int $newInternalId,
    ): self
    {
        return new self(sprintf(
            'External ID "%s" for type "%s" and provider "%s" is already mapped to internal ID "%s"; cannot map it to "%s".',
            (string) $externalId,
            $type,
            $provider,
            (string) $existingInternalId,
            (string) $newInternalId,
        ));
    }
}
