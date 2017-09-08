<?php

namespace Pckg\Database;

/**
 * Class Object
 *
 * @package Pckg\Database
 */
class Object implements \ArrayAccess
{

    /**
     * @var array
     * @T00D00 - rename this to $_data
     */
    protected $data = [];

    /**
     * @var array
     * @T00D00 - rename this to $_original
     */
    protected $original = [];

    /**
     * @param array $values
     */
    public function __construct($data = [])
    {
        $this->data = $data ?? [];
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function setData(array $data = [])
    {
        $this->data = $data;

        return $this;
    }

    public function data($key = null)
    {
        return $key
            ? (array_key_exists($key, $this->data)
                ? $this->data[$key]
                : null)
            : $this->data;
    }

    public function setOriginal(array $data = [])
    {
        $this->original = $data;

        return $this;
    }

    public function original($key = null)
    {
        return $key
            ? (array_key_exists($key, $this->original)
                ? $this->original[$key]
                : null)
            : $this->original;
    }

    public function isOriginal($key = null)
    {
        return $this->data($key) == $this->original($key);
    }

    public function isDirty($key = null)
    {
        return $this->data($key) != $this->original($key);
    }

    public function setOriginalFromData()
    {
        $this->original = $this->data;

        return $this;
    }

    public function keyExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function getValue($key)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : null;
    }

    public function __unset($key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @param $key
     * @param $val
     *
     * @return Object
     */
    public function set($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->__set($k, $v);
            }

            return $this;
        } else {
            return $this->__set($key, $val);
        }
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function __get($key)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : null;
    }

    /**
     * @param $key
     * @param $val
     *
     * @return $this
     */
    public function __set($key, $val)
    {
        $this->data[$key] = $val;

        return $this;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->data) && $this->data[$key];
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->data;
    }

    function jsonSerialize()
    {
        return $this->__toArray();
    }

    public function dd()
    {
        dd($this->data());

        return $this;
    }

}