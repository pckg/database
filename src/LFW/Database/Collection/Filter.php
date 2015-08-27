<?php

namespace LFW\Database\Collection;

use LFW\Database\Collection;

/**
 * Class Filter
 * @package LFW\Database\Collection
 */
class Filter extends Collection
{

    /**
     * @param $filterBy
     * @param $val
     * @param string $comparator
     * @return array
     * @throws \Exception
     */
    public function getFiltered($filterBy, $val, $comparator = '==')
    {
        $arrFiltered = [];

        foreach ($this->collection AS $row) {
            $objectValue = $this->getValue($row, $filterBy);

            if ((($comparator == '==')
                && ((is_array($val) && in_array($objectValue, $val))
                    || ($objectValue == $val)
                )
                || (($comparator == '===')
                    && ($objectValue === $val)
                )
                || (($comparator == '<=')
                    && ($objectValue <= $val)
                )
                || (($comparator == '>=')
                    && ($objectValue >= $val)
                )
                || (($comparator == '!=')
                    && ($objectValue != $val)
                )
                || (($comparator == '!==')
                    && ($objectValue !== $val)
                )
            )
            ) {
                $arrFiltered[] = $row;
            }
        }

        return $arrFiltered;
    }
}