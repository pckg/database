<?php

namespace Pckg\Database\Collection;

use Pckg\Database\Collection;

/**
 * Class Sort
 * @package Pckg\Database\Collection
 */
class Sort extends Collection
{

    /**
     * @param $sortBy
     * @return array
     */
    public function getSorted($sortBy)
    {
        $arrSort = [];

        foreach ($this->groupAndSort($sortBy) AS $group) {
            foreach ($group AS $row) {
                $arrSort[] = $row;
            }
        }

        return $arrSort;
    }

    /**
     * @param $sortBy
     * @return array
     */
    public function groupAndSort($sortBy)
    {
        $arr = [];

        foreach ($this->collection AS $row) {
            $arr[$row->{$sortBy}()][] = $row;
        }

        ksort($arr);

        return $arr;
    }
}