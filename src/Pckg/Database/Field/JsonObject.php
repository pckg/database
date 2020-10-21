<?php namespace Pckg\Database\Field;

use Pckg\Collection;
use Pckg\Collection\CollectionHelper;
use Pckg\Database\Record;

/**
 * Class JsonArray
 * @package Pckg\Database\Field
 * @see Collection
 * @method $this push($item)
 * @see Collection::push()
 * @method $this unique()
 * @see Collection::unique()
 * @method $this removeEmpty()
 * @see Collection::removeEmpty()
 * @method $this has()
 * @see Collection::has()
 */
class JsonObject extends AbstractField implements \Iterator, \ArrayAccess
{

    use CollectionHelper;

    /**
     * @param mixed|mixed $value
     * @return mixed|void
     * @throws \Exception
     */
    public function validateValue($value)
    {
        /**
         * $foo->bar = null;
         */
        if (is_null($value)) {
            $this->collection = [];
            return;
        }

        /**
         * When set as $foo->bar = [];
         */
        if (is_array($value)) {
            $this->collection = $value;
            return;
        }

        /**
         * When set as $foo->bar = new stdClass();
         */
        if (is_object($value)) {
            $this->collection = (array)$value;
            return;
        }

        /**
         * When set as $foo->bar = '["something"]';
         */
        if (is_string($value) && substr($value, 0, 1) === '[') {
            $this->collection = json_decode($value, true) ?? [];
            return;
        }

        /**
         * When set as $foo->bar = '{"something":"something"}';
         */
        if (is_string($value) && substr($value, 0, 1) === '{') {
            $this->collection = json_decode($value, true) ?? [];
            return;
        }

        throw new \Exception('Invalid JsonObject data');
    }

    /**
     * @return mixed|\stdClass|string
     */
    public function jsonSerialize()
    {
        return $this->collection ?? new \stdClass();
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize(), JSON_FORCE_OBJECT) ?? '{}';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->__toArray();
    }

    /**
     * @return mixed
     */
    public function __toArray()
    {
        return $this->collection;
    }

    /**
     * @return $this
     */
    public function empty()
    {
        $this->set([]);

        return $this;
    }

    /**
     * @return mixed|Collection
     */
    public function collect()
    {
        return collect($this->collection);
    }

    /**
     * @param $field
     * @return mixed
     */
    public function __get($field)
    {
        if ($field === 'collection') {
            return $this->collection;
        }

        return $this->collection[$field] ?? null;
    }

    /**
     * @param $method
     * @param $args
     * @return $this
     */
    public function __call($method, $args)
    {
        $this->markDirty();
        $newCollection = collect($this->collection)->{$method}(...$args);

        /**
         * ->has(), ->first(), ->count(), ...
         * Issue: collection of collections?
         */
        if (!($newCollection instanceof Collection)) {
            return $newCollection;
        }

        /**
         * Get items from collection.
         * Always rekey array?
         */
        $newCollection = $newCollection->{($this instanceof JsonArray) ? 'values' : 'all'}();

        if ($newCollection !== $this->collection) {
            $this->collection = $newCollection;
        }

        return $this;
    }

    public function __set($key, $value)
    {
        $this->markDirty();
        $this->collection[$key] = $value;

        return $this;
    }

}