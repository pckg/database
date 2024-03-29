<?php

namespace Pckg\Database;

/**
 * Class Field
 *
 * Presents custom objectized field in record
 *
 * @package Pckg\Database
 */
class Field
{
    protected $value;

    /**
     * @return $this
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function __toBool()
    {
        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * @return int
     */
    public function __toInt()
    {
        return 1;
    }
}
