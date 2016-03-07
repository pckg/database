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
    public $data = [];

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

    /**
     * @param array $values
     */
    public function __construct($values = [])
    {
        foreach ($values as $key => $val) {
            $this->__set($key, $val);
        }
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
     * @return $this
     */
    public function __set($key, $val)
    {
        $this->data[$key] = $val;

        return $this;
    }

    /**
     * @param $key
     * @param $val
     *
     * @return Object
     */
    public function set($key, $val)
    {
        return $this->__set($key, $val);
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

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->__toArray();
    }

}