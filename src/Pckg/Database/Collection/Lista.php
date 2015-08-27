<?php

namespace Pckg\Database\Collection;

use Pckg\Database\Collection;

/**
 * Class Lista
 * @package Pckg\Database\Collection
 */
class Lista extends Collection
{

    /**
     * @return array
     */
    public function getIdAsKey()
    {
        $return = [];

        foreach ($this->collection AS $row) {
            $return[$row->getID()] = $row;
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $return = [];

        foreach ($this->collection AS $i => $row) {
            if (!is_array($row)) {
                $row = $row->__toArray();
            }

            foreach (["title", "slug", "name", "email", "key", "id"] AS $key) {
                if (isset($row[$key])) {
                    $return[] = $row[$key];
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getListID()
    {
        $return = [];

        foreach ($this->collection AS $i => $row) {
            if (!is_array($row)) {
                $row = $row->__toArray();
            }

            foreach (["title", "slug", "name", "email", "key", "id"] AS $key) {
                if (isset($row[$key])) {
                    $return[$row['id']] = $row[$key];
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @param $callback
     * @return array
     */
    public function getCustomList($callback)
    {
        $return = [];

        foreach ($this->collection AS $i => $row) {
            $realRow = $callback($row);

            if ($realRow) {
                $return[$row->getId()] = $realRow;
            }
        }

        return $return;
    }
}