<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * One page of a list endpoint: its items plus the pagination meta.
 *
 * @template T of Model
 * @implements IteratorAggregate<int, T>
 * @implements ArrayAccess<int, T>
 */
final class Page implements IteratorAggregate, Countable, ArrayAccess
{
    /** @var array<int, T> */
    public readonly array $items;

    public readonly PageMeta $meta;

    /** @param array<int, T> $items */
    public function __construct(array $items = [], ?PageMeta $meta = null)
    {
        $this->items = array_values($items);
        $this->meta = $meta ?? PageMeta::empty();
    }

    /** @return Traversable<int, T> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /** @return T|null */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('fopost: a Page is read only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('fopost: a Page is read only');
    }
}
