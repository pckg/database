<?php namespace Pckg\Database\Record;

trait Transformations
{
    
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
    
}