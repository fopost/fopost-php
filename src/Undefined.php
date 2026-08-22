<?php

declare(strict_types=1);

namespace Fopost\Sdk;

/**
 * Sentinel for "the caller did not pass this", so a partial update sends only
 * the fields that were named. Distinct from null, which clears a field.
 */
enum Undefined
{
    case Value;

    public static function is(mixed $value): bool
    {
        return $value === self::Value;
    }
}
