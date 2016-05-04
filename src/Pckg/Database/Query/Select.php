<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Select
 * @package Pckg\Database\Query
 */
class Select extends Query
{
    /**
     * @var
     */
    protected $select = ['*'];

    public function fields($fields)
    {
        $this->select = $fields;

        return $this;
    }

    // builders
    /**
     * @return string
     */
    function buildSQL()
    {
        $sql = "SELECT " . $this->buildSelect() . " " .
            "FROM `" . $this->table . "` " .
            ($this->join ? $this->buildJoin() : '') .
            $this->buildWhere() .
            ($this->having ? $this->buildHaving() : '') .
            ($this->groupBy ? ' GROUP BY ' . $this->groupBy : '') .
            ($this->orderBy ? ' ORDER BY ' . ($this->orderBy == 'id' ? $this->table . "." . $this->orderBy : $this->orderBy) : '') .
            ($this->limit ? ' LIMIT ' . $this->limit : '');

        return $sql;
    }

    public function buildBinds()
    {
        return $this->getBinds(['select', 'from', 'join', 'where', 'having', 'group', 'order', 'limit']);
    }

    public function buildSelect()
    {
        return implode(', ', $this->select);
    }

    /**
     * @param $select
     *
     * @return $this
     */
    public function addSelect($select)
    {
        $this->select[] = $select;

        return $this;
    }
}