<?php

namespace LFW\Database;

use ArrayAccess;
use Exception;
use LFW\Database\Collection\Iterator;

/**
 * Class Collection
 * @package LFW\Database
 */
class Collection extends Iterator implements ArrayAccess
{

    public function push($item)
    {
        $this->collection[] = $item;

        return $this;
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->collection;
    }

    /* helper */
    /**
     * @param $object
     * @param $key
     * @return mixed
     * @throws Exception
     */
    protected function getValue($object, $key)
    {
        if (is_object($object) && method_exists($object, $key)) {
            return $object->{$key}();

        } else if (is_object($object) && isset($object->{$key})) {
            return $object->{$key};

        } else if (is_array($object) && array_key_exists($key, $object)) {
            return $object[$key];

        }

        throw new Exception("Cannot find key $key in object " . get_class($object));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getKey($key)
    {
        return $this->collection[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function keyExists($key)
    {
        return isset($this->collection[$key]);
    }

    /* strategies */
    /**
     * @return Collection
     */
    public function getIdAsKey()
    {
        $list = new Collection\Lista($this->collection);

        return new Collection($list->getIdAsKey());
    }

    /**
     * @return Collection
     */
    public function getList()
    {
        $list = new Collection\Lista($this->collection);

        return new Collection($list->getList());
    }

    /**
     * @return Collection
     */
    public function getListID()
    {
        $list = new Collection\Lista($this->collection);

        return new Collection($list->getListID());
    }

    /**
     * @param $callback
     * @return Collection
     */
    public function getCustomList($callback)
    {
        $list = new Collection\Lista($this->collection);

        return new Collection($list->getCustomList($callback));
    }

    /**
     * @param $foreign
     * @return Collection
     */
    public function getTree($foreign)
    {
        $tree = new Collection\Tree($this->collection);

        return new Collection($tree->getHierarchy($foreign));
    }

    /**
     * @param $sortBy
     * @return Collection
     */
    public function sortBy($sortBy)
    {
        $sort = new Collection\Sort($this->collection);

        return new Collection($sort->getSorted($sortBy));
    }

    /**
     * @param $groupBy
     * @return Collection
     */
    public function groupBy($groupBy)
    {
        $group = new Collection\Group($this->collection);

        return new Collection($group->getGroupped($groupBy));
    }

    /**
     * @param $filterBy
     * @param $value
     * @param string $comparator
     * @return Collection
     */
    public function filter($filterBy, $value, $comparator = '==')
    {
        $filter = new Collection\Filter($this->collection);

        return new Collection($filter->getFiltered($filterBy, $value, $comparator));
    }

    /**
     * @param $limitCount
     * @param int $limitOffset
     * @return Collection
     */
    public function limit($limitCount, $limitOffset = 0)
    {
        $limit = new Collection\Limit($this->collection);

        return new Collection($limit->getLimited($limitCount, $limitOffset));
    }

    /**
     * @return null
     */
    public function first()
    {
        $limit = new Collection\Limit($this->collection);

        return $limit->getFirst();
    }

    public function each($callback)
    {
        foreach ($this->collection as $item) {
            $callback($item);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->collection[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }

}