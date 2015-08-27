<?php

namespace LFW\Database\Collection;

use LFW\Database\Collection;

/**
 * Class Group
 * @package LFW\Database\Collection
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