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
            $this->data = null;
            return;
        }

        /**
         * When set as $foo->bar = [];
         */
        if (is_array($value)) {
            $this->data = array_slice(array_values($value), 0, 2);
            $this->data[0] = (float)($this->data[0] ?? 0);
            $this->data[1] = (float)($this->data[1] ?? 0);
            return;
        }

        /**
         * When set as $foo->bar = '["something"]';
         */
        if (is_string($value)) {
            $this->data = array_slice(explode(';', $value), 0, 2);
            $this->data[0] = (float)($this->data[0] ?? 0);
            $this->data[1] = (float)($this->data[1] ?? 0);
            return;
        }

        throw new \Exception('Invalid Point data');
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->data ? "GeomFromText(?)" : '?';
    }

    /**
     * @return array|string
     */
    public function getBind()
    {
        return $this->data ? 'POINT(' . ((float)$this->data[0]) . ' ' . ((float)$this->data[1]) . ')' : [];
    }

    /**
     * @return null|array
     */
    public function jsonSerialize()
    {
        if (!$this->data) {
            return null;
        }

        return [
            'x' => $this->data[0],
            'y' => $this->data[1],
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->data) {
            return 'NULL';
        }

        return implode(';', $this->data);
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

        if (!$this->data) {
            $this->data = [0, 0];
        }

        if ($key === 'x') {
            $this->data[0] = (float)$val;
        } else if ($key === 'y') {
            $this->data[1] = (float)$val;
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
            return $this->data[0];
        } else if ($key === 'y') {
            return $this->data[1];
        }

        throw new \Exception('Unknown Point property');
    }


}