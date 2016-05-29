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
    protected $select = [];

    protected $count = false;

    public function count($count = true)
    {
        $this->count = $count;

        return $this;
    }

    public function isCounted()
    {
        return $this->count;
    }

    public function table($table)
    {
        $this->table = $table;

        if (!in_array('`' . $table . '`.*', $this->select)) {
            $this->select[] = '`' . $table . '`.*';
        }

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
        return ($this->count ? 'SQL_CALC_FOUND_ROWS ' : '') . implode(', ', $this->select);
    }

    public function select($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->select = $fields;

        return $this;
    }

    /**
     * @param $select
     *
     * @return $this
     */
    public function addSelect($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            $this->select[] = $field;
        }

        return $this;
    }

    public function prependSelect($fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            array_unshift($this->select, $field);
        }

        return $this;
    }

}