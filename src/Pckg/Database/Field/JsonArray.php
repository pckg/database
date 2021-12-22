<?php

namespace Pckg\Database\Field;

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
class JsonArray extends JsonObject implements \Countable
{

    public function empty()
    {
        $this->set([]);

        return $this;
    }

    /**
     * @param mixed|mixed $value
     * @return mixed|void
     * @throws \Exception
     */
    public function validateValue($value)
    {
        /**
         * When set as $foo->bar = [];
         */
        if (is_array($value)) {
            $this->collection = array_values($value);
            return;
        }

        /**
         * When set as $foo->bar = '["something"]';
         */
        if (is_string($value) && substr($value, 0, 1) === '[') {
            $this->collection = array_values(json_decode($value, true) ?? []);
            return;
        }

        /**
         * When set as $foo->bar = '{"something":"something"}';
         */
        if (is_string($value) && substr($value, 0, 1) === '{') {
            $this->collection = array_values(json_decode($value, true) ?? []);
            return;
        }

        /**
         * $foo->bar = null;
         */
        if (is_null($value)) {
            $this->collection = [];
            return;
        }

        throw new \Exception('Invalid JsonArray data');
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->collection ?? [];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->__toArray() ?? [];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize()) ?? '[]';
    }
}
