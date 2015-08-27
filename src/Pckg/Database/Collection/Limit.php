<?php

namespace Pckg\Database\Collection;

use Pckg\Database\Collection;
use LimitIterator;

/**
 * Class Limit
 * @package Pckg\Database\Collection
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