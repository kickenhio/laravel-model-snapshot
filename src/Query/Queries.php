<?php namespace Kickenhio\LaravelSqlSnapshot\Query;

use ArrayIterator;
use IteratorAggregate;

class Queries implements IteratorAggregate
{
    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    /**
     * @param Queries $queries
     *
     * @return Queries
     */
    public function merge(Queries $queries): Queries
    {
        $this->items = array_merge($this->items, $queries->items);
        return $this;
    }

    /**
     * @param string $query
     *
     * @return Queries
     */
    public function append(string $query): Queries
    {
        $this->items[] = $query;
        return $this;
    }

    /**
     * @return Queries
     */
    public function withoutComments(): Queries
    {
        return new static (array_filter($this->items, function (string $query){
             return substr($query, 0, 2) !== '--';
        }));
    }

    /**
     * @return ArrayIterator
     */
    function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}