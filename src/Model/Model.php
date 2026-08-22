<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Base for every response model.
 *
 * The API is not consistent about its wire casing: posts come back snake_case,
 * accounts camelCase, workspaces a mix. Every field is therefore read under
 * both spellings. The untouched payload stays on `raw`, so a server side
 * addition is never dropped.
 */
abstract class Model implements JsonSerializable
{
    /** @var array<string, mixed> */
    public readonly array $raw;

    /** @param array<string, mixed> $raw */
    protected function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /** @param mixed $data */
    abstract public static function fromArray(mixed $data): static;

    /**
     * @param mixed $data
     * @return array<int, static>
     */
    public static function listFrom(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $out[] = static::fromArray($item);
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }

    /** Any field on the payload, including ones the SDK does not model yet. */
    public function get(string $name): mixed
    {
        return self::field($this->raw, $name);
    }

    /** @param array<string, mixed> $data */
    protected static function field(array $data, string $name): mixed
    {
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }
        $camel = self::toCamel($name);
        if ($camel !== $name && array_key_exists($camel, $data)) {
            return $data[$camel];
        }
        $snake = self::toSnake($name);
        if ($snake !== $name && array_key_exists($snake, $data)) {
            return $data[$snake];
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    protected static function str(array $data, string $name): ?string
    {
        $value = self::field($data, $name);

        return is_string($value) ? $value : (is_int($value) || is_float($value) ? (string) $value : null);
    }

    /** @param array<string, mixed> $data */
    protected static function requiredStr(array $data, string $name): string
    {
        return self::str($data, $name) ?? '';
    }

    /** @param array<string, mixed> $data */
    protected static function int(array $data, string $name): ?int
    {
        $value = self::field($data, $name);
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    protected static function bool(array $data, string $name): ?bool
    {
        $value = self::field($data, $name);

        return is_bool($value) ? $value : null;
    }

    /** @param array<string, mixed> $data */
    protected static function date(array $data, string $name): ?DateTimeImmutable
    {
        $value = self::field($data, $name);
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function map(array $data, string $name): array
    {
        $value = self::field($data, $name);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, mixed>
     */
    protected static function seq(array $data, string $name): array
    {
        $value = self::field($data, $name);

        return is_array($value) ? array_values($value) : [];
    }

    /** @param array<string, mixed> $data */
    protected static function nested(array $data, string $name): ?array
    {
        $value = self::field($data, $name);

        return is_array($value) ? $value : null;
    }

    private static function toCamel(string $name): string
    {
        $parts = explode('_', $name);
        $head = array_shift($parts) ?? '';

        return $head . implode('', array_map(static fn (string $p): string => ucfirst($p), $parts));
    }

    private static function toSnake(string $name): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
