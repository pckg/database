<?php namespace Pckg\Database\Record;

use Pckg\Database\Object;

trait Transformations
{

    protected $toArray = [];

    protected $toJson = [];

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
     * @return array
     */
    public function toArray($values = null, $depth = 6, $withToArray = true)
    {
        return $this->__toArray($values, $depth, $withToArray);
    }

    public function toObject($values = null, $depth = 6, $withToArray = true)
    {
        $array = $this->toArray($values, $depth, $withToArray);
        foreach ($array as $key => $val) {
            $array[$key] = new Object($val);
        }

        return new Object($array);
    }

    /**
     * @return array
     */
    public function __toArray($values = null, $depth = 6, $withToArray = true)
    {
        $return = [];

        if (!$depth) {
            return [];
        }

        if (is_null($values)) {
            $values = $this->data;
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
            if (is_object($value)) {
                if (method_exists($value, '__toArray')) {
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

    public function toJSON()
    {
        return json_encode($this->jsonSerialize());
    }

    function jsonSerialize()
    {
        return $this->__toArray();
    }

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