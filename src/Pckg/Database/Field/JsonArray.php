<?php namespace Pckg\Database\Field;

use Pckg\Collection\CollectionHelper;

class JsonArray implements Stringifiable, \JsonSerializable, \Iterator, \ArrayAccess
{

    use CollectionHelper;

    protected $collection;

    public function __construct($value)
    {
        $this->collection = $value;
    }

    public function set($decoded)
    {
        $this->collection = json_encode($decoded);

        return $this;
    }

    public function __toString()
    {
        return json_encode($this->collection ?? []);
    }

    public function __toArray()
    {
        return json_decode($this->collection, true) ?? [];
    }

    public function jsonSerialize()
    {
        return $this->__toArray();
    }

    public function decapsulate()
    {
        return $this->__toArray();
    }

}