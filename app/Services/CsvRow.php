<?php

namespace App\Services;

use ArrayIterator;
use DomainException;
use IteratorAggregate;
use Traversable;

class CsvRow implements IteratorAggregate
{
    private readonly array $items;

    public function __construct(array $values, array $header)
    {
        if (count($values) !== count($header)) {
            throw new DomainException('Not match counts of values and headings');
        }

        $this->items = array_combine($header, $values);
    }

    public function has(string $heading): bool
    {
        return isset($this->items[$heading]);
    }

    public function get(string $headings): string
    {
        return trim($this->items[$headings]);
    }

    public function reject(string $heading): self
    {
        if (!$this->has($heading)) {
            return $this;
        }

        $newItems = $this->items;
        unset($newItems[$heading]);

        return new self(array_values($newItems), array_keys($newItems));
    }

    public function isEmpty(string $heading): bool
    {
        return $this->get($heading) === '';
    }

    public function header(): array
    {
        return array_keys($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
