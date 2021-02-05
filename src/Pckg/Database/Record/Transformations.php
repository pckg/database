<?php

namespace Pckg\Database\Record;

use Pckg\Database\Field\Stringifiable;
use Pckg\Database\Obj;
use Throwable;

/**
 * Class Transformations
 *
 * @package Pckg\Database\Record
 */
trait Transformations
{

    /**
     * @var array
     */
    protected $toArray = [];

    /**
     * @var array
     */
    protected $toJson = [];

    /**
     * @var array
     */
    protected $protect = [];

    /**
     * @param array $items
     *
     * @return $this
     */
    public function addToArray($items = [])
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->toArray[] = $item;
        }

        return $this;
    }

    /**
     * @param null $values
     * @param int  $depth
     * @param bool $withToArray
     *
     * @return Object
     */
    public function toObject($values = null, $depth = 6, $withToArray = true)
    {
        $array = $this->toArray($values, $depth, $withToArray);
        foreach ($array as $key => $val) {
            $array[$key] = new Obj($val);
        }

        return new Obj($array);
    }

    /**
     * @return array
     */
    public function toArray($values = null, $depth = 6, $withToArray = true)
    {
        return $this->__toArray($values, $depth, $withToArray);
    }

    /**
     * @return array
     */
    public function __toArray($values = null, $depth = 6, $withToArray = true, $removeProtected = true)
    {
        $return = [];

        if (!$depth) {
            return [];
        }

        if (is_null($values)) {
            $values = $this->data; // should we call getters here?
            if ($withToArray && $this->toArray) {
                foreach ($this->getToArrayValues() as $key => $value) {
                    $values[$key] = $value;
                }
                foreach ($this->getExtensionValues() as $key => $value) {
                    $values[$key] = $value;
                }
            }
        }

        foreach ($values as $key => $value) {
            /**
             * Skip protected keys.
             */
            if ($removeProtected && in_array($key, $this->protect)) {
                continue;
            }

            if (is_object($value)) {
                /**
                 * @T00D00 - Should we force  Strigifiable interface to be used?
                 * What are the use cases for other options?
                 */
                if ($value instanceof Stringifiable) { // will be processed later because it's a literal
                    $return[$key] = $value;
                } else if (method_exists($value, '__toArray')) {
                    $return[$key] = $value->__toArray(null, $depth - 1, $withToArray);
                } else {
                    $return[$key] = (string)$value;
                }
            } else if (is_array($value)) {
                $return[$key] = $this->__toArray($value, $depth - 1, $withToArray);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        try {
            $json = json_encode($this->jsonSerialize(), JSON_OBJECT_AS_ARRAY | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (Throwable $e) {
        }

        return $json ?? 'null';
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return $this->__toArray();
    }

    /**
     *
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * @param $map
     *
     * @return array
     */
    public function transform($map)
    {
        if (is_callable($map)) {
            return $map($this);
        }

        $data = [];
        foreach ($map as $key) {
            $data[$key] = $this->{$key};
        }

        return $data;
    }
}
