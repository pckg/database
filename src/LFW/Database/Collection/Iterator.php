<?php

namespace LFW\Database\Collection;

/**
 * Class Iterator
 * @package LFW\Database\Collection
 */
class Iterator extends \EmptyIterator
{
    /**
     * @var array
     */
    protected $collection = [];

    /**
     * @param array $array
     */
    public function __construct($array = [])
    {
        $this->collection = $array;
    }

    /**
     * @return mixed
     */
    public function rewind()
    {
        return reset($this->collection);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return key($this->collection) !== null;
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->collection;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->collection);
    }
}