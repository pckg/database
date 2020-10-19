<?php namespace Pckg\Database\Field;

use Pckg\Collection;
use Pckg\Collection\CollectionHelper;
use Pckg\Database\Record;

/**
 * Class JsonArray
 * @package Pckg\Database\Field
 */
class Point extends AbstractField
{

    /**
     * Transform string or array?
     *
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
            $this->collection = null;
            return;
        }

        /**
         * When set as $foo->bar = [];
         */
        if (is_array($value)) {
            $this->collection = array_slice(array_values($value), 0, 2);
            $this->collection[0] = (float)($this->collection[0] ?? 0);
            $this->collection[1] = (float)($this->collection[1] ?? 0);
            return;
        }

        /**
         * When set as $foo->bar = '["something"]';
         */
        if (is_string($value)) {
            $this->collection = array_slice(explode(';', $value), 0, 2);
            $this->collection[0] = (float)($this->collection[0] ?? 0);
            $this->collection[1] = (float)($this->collection[1] ?? 0);
            return;
        }

        throw new \Exception('Invalid Point data');
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->collection ? "GeomFromText(?)" : '?';
    }

    /**
     * @return array|string
     */
    public function getBind()
    {
        return $this->collection ? 'POINT(' . ((float)$this->collection[0]) . ' ' . ((float)$this->collection[1]) . ')' : [];
    }

    /**
     * @return null|array
     */
    public function jsonSerialize()
    {
        if (!$this->collection) {
            return null;
        }

        return [
            'x' => $this->collection[0],
            'y' => $this->collection[1],
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->collection) {
            return 'NULL';
        }

        return implode(';', $this->collection);
    }

    /**
     * Setter for ->x and ->y
     * 
     * @param $key
     * @param $val
     * @return $this
     * @throws \Exception
     */
    public function __set($key, $val)
    {
        if (!in_array($key, ['x', 'y'])) {
            throw new \Exception('Unknown Point property');
        }

        if (!$this->collection) {
            $this->collection = [0, 0];
        }

        if ($key === 'x') {
            $this->collection[0] = (float)$val;
        } else if ($key === 'y') {
            $this->collection[1] = (float)$val;
        }

        return $this;
    }

    /**
     * Getter for ->x and ->y
     * 
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function __get($key)
    {
        if ($key === 'x') {
            return $this->collection[0];
        } else if ($key === 'y') {
            return $this->collection[1];
        }

        throw new \Exception('Unknown Point property');
    }


}