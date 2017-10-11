<?php namespace Pckg\Database;

    /**
     * Presents custom objectized field in record
     */
/**
 * Class Field
 *
 * @package Pckg\Database
 */
class Field
{

    /**
     * @var
     */
    protected $value;

    /**
     * @param $value
     *
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