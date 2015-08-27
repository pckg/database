<?php

namespace Pckg\Database\Collection;

use Pckg\Database\Collection;

/**
 * Class Group
 * @package Pckg\Database\Collection
 */
class Group extends Collection
{
    /**
     * @var
     */
    protected $groupBy;

    /* builds groups */
    /**
     * @param $groupBy
     * @return array
     * @throws \Exception
     */
    public function getGroupped($groupBy)
    {
        $arrGroupped = [];

        foreach ($this->collection AS $row) {
            $arrGroupped[$this->getValue($row, $this->groupBy)][] = $row;
        }

        return $arrGroupped;
    }
}