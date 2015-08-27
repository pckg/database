<?php

namespace LFW\Database\Collection;

use LFW\Database\Collection;
use LimitIterator;

/**
 * Class Limit
 * @package LFW\Database\Collection
 */
class Limit extends Collection
{

    /**
     * @param $limitCount
     * @param int $limitOffset
     * @return array
     */
    public function getLimited($limitCount, $limitOffset = 0)
    {
        $arrLimited = [];

        foreach (new LimitIterator($this, $limitOffset, $limitCount) AS $row) {
            $arrLimited[] = $row;
        }

        return $arrLimited;
    }

    /**
     * @return null
     */
    public function getFirst()
    {
        return isset($this->collection[0]) ? $this->collection[0] : null;
    }
}