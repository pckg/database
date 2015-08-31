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
        $nl = "";

        $sql = "SELECT " . $this->buildSelect() . " " . $nl .
            "FROM `" . $this->table . "` " . $nl .
            ($this->join ? $this->buildJoin() . $nl : '') .
            ($this->where ? $this->buildWhere() . $nl : '') .
            ($this->having ? $this->buildHaving() . $nl : '') .
            ($this->groupBy ? ' GROUP BY ' . $this->groupBy . $nl : '') .
            ($this->orderBy ? ' ORDER BY ' . ($this->orderBy == 'id' ? $this->table . "." . $this->orderBy : $this->orderBy) . $nl : '') .
            ($this->limit ? ' LIMIT ' . $this->limit : '');

        //d($sql);

        return $sql;
    }

    /**
     * @param $select
     * @return $this
     */
    public function addSelect($select)
    {
        $this->select[] = $select;

        return $this;
    }

    public function buildSelect()
    {
        return implode(', ', $this->select);
    }
}