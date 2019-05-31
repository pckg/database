<?php namespace Pckg\Database;

use ArrayAccess;
use JsonSerializable;

/**
 * Class Object
 *
 * @package Pckg\Database
 */
class Obj implements ArrayAccess, JsonSerializable
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

    public function getToArrayValues()
    {
        return [];
    }

    public function getToJsonValues()
    {
        return [];
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasKey($key)
    {
        if (array_key_exists($key, $this->data)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $values
     */
    public function __construct(array $data = [])
    {
        $this->data = $data ?? [];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return Object
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
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
     * @param mixed $offset
     *
     * @return Object
     */
    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }

    /**
     * @param $key
     *
     * @return $this
     */
    public function __unset($key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @param mixed $offset
     *
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
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
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data = [])
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setOriginal(array $data = [])
    {
        $this->original = $data;

        return $this;
    }

    /**
     * @param null $key
     *
     * @return bool
     */
    public function isOriginal($key = null)
    {
        return $this->data($key) == $this->original($key);
    }

    /**
     * @param null $key
     *
     * @return array|mixed|null
     */
    public function data($key = null)
    {
        return $key
            ? (array_key_exists($key, $this->data)
                ? $this->data[$key]
                : null)
            : $this->data;
    }

    /**
     * @param null $key
     *
     * @return array|mixed|null
     */
    public function original($key = null)
    {
        return $key
            ? (array_key_exists($key, $this->original)
                ? $this->original[$key]
                : null)
            : $this->original;
    }

    /**
     * @param null $key
     *
     * @return bool
     */
    public function isDirty($key = null)
    {
        return $this->data($key) != $this->original($key);
    }

    public function getDirtyData()
    {
        $data = $this->data();
        $original = $this->original();
        $diff = [];

        foreach ($data as $key => $val) {
            if (isset($original[$key]) && $original[$key] == $val) {
                continue;
            }

            $diff[$key] = $val;
        }

        return $diff;
    }

    /**
     * @return $this
     */
    public function setOriginalFromData()
    {
        $this->original = $this->data;

        return $this;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function keyExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getValue($key)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : null;
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
        }

        return $this->__set($key, $val);
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
     * @return array
     */
    function jsonSerialize()
    {
        $array = $this->__toArray();

        return $array ? $array : new \stdClass();
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->data;
    }

}