<?php

namespace Pckg\Database;

/**
 * Class Object
 * @package Pckg\Database
 */
class Object
{

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @param array $values
     */
    public function __construct($values = [])
    {
        foreach ($values as $key => $val) {
            $this->__set($key, $val);
        }
    }


    /**
     * @param $key
     * @param $val
     * @return $this
     */
    public function __set($key, $val)
    {
        $this->values[$key] = $val;

        return $this;
    }

    /**
     * @param $key
     * @param $val
     * @return Object
     */
    public function set($key, $val)
    {
        return $this->__set($key, $val);
    }

    /**
     * @param $key
     * @return null
     */
    public function __get($key)
    {
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : null;
    }

    /**
     * @param $key
     * @return null
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($this->values, $key) && $this->values[$key];
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->__toArray();
    }

}