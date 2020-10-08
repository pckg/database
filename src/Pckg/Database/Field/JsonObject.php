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
            $this->data = [];
            return;
        }

        /**
         * When set as $foo->bar = [];
         */
        if (is_array($value)) {
            $this->data = $value;
            return;
        }

        /**
         * When set as $foo->bar = '["something"]';
         */
        if (is_string($value) && substr($value, 0, 1) === '[') {
            $this->data = json_decode($value, true) ?? [];
            return;
        }

        /**
         * When set as $foo->bar = '{"something":"something"}';
         */
        if (is_string($value) && substr($value, 0, 1) === '{') {
            $this->data = json_decode($value, true) ?? [];
            return;
        }

        throw new \Exception('Invalid JsonObject data');
    }

    /**
     * @return mixed|\stdClass|string
     */
    public function jsonSerialize()
    {
        return $this->data ?? new \stdClass();
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize(), JSON_FORCE_OBJECT) ?? '{}';
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
        return collect($this->data);
    }

    /**
     * @param $field
     * @return mixed
     */
    public function __get($field)
    {
        if ($field === 'collection') {
            return $this->data;
        }
    }

    /**
     * @param $method
     * @param $args
     * @return $this
     */
    public function __call($method, $args)
    {
        $this->markDirty();
        $newCollection = collect($this->data)->{$method}(...$args);

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

        if ($newCollection !== $this->data) {
            $this->data = $newCollection;
        }

        return $this;
    }

}