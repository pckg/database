<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Select
 *
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

    public function alias($alias)
    {
        $this->alias = $alias;

        if (!in_array('`' . $alias . '`.*', $this->select)) {
            $this->select[] = '`' . $alias . '`.*';
        }

        if (in_array('`' . $this->table . '`.*', $this->select)) {
            unset($this->select[array_search('`' . $this->table . '`.*', $this->select)]);
        }

        return $this;
    }

    // builders
    /**
     * @return string
     */
    function buildSQL()
    {
        $parts = ["SELECT " . $this->buildSelect(), "FROM " . $this->buildTable()];

        if ($this->join) {
            $parts[] = $this->buildJoin();
        }

        if ($this->where->hasChildren()) {
            $parts[] = $this->buildWhere();
        }

        if ($this->groupBy) {
            $parts[] = 'GROUP BY ' . $this->groupBy;
        }

        if ($this->having->hasChildren()) {
            $parts[] = $this->buildHaving();
        }

        if ($this->orderBy) {
            $parts[] = 'ORDER BY ' . ($this->orderBy == 'id' ? '`' . $this->table . "`.`" . $this->orderBy . '` ASC' : $this->orderBy);
        }

        if ($this->limit) {
            $parts[] = 'LIMIT ' . $this->limit;
        }

        $sql = implode(' ', $parts);

        return $sql;
    }

    public function buildSelect()
    {
        if (!$this->select) {
            $this->select[] = $this->table . '.*';
        }

        $keys = [];
        foreach ($this->select as $key => $select) {
            if (is_numeric($key)) {
                $keys[] = $select;

            } else {
                $keys[] = $select . ' AS `' . $key . '`';

            }
        }

        return ($this->count ? 'SQL_CALC_FOUND_ROWS ' : '') . implode(', ', $keys);
    }

    public function buildTable()
    {
        return '`' . $this->table . '`' . ($this->alias
            ? ' AS `' . $this->alias . '`'
            : '');
    }

    public function buildBinds()
    {
        return $this->getBinds(['select', 'from', 'join', 'where', 'having', 'group', 'order', 'limit']);
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

        foreach ($fields as $key => $field) {
            if (is_numeric($key)) {
                $this->select[] = $field;
            } else {
                $this->select[$key] = $field;
            }
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

    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return Delete
     */
    public function transformToDelete()
    {
        $delete = new Delete();

        $delete->setTable($this->table);
        $delete->getWhere()->setChildren($this->where->getChildren());
        $delete->setBind(['where' => $this->getBinds('where')]);
        foreach ($this->join as $join) {
            $delete->join($join);
        }

        return $delete;
    }

    /**
     * @return Delete
     */
    public function transformToInsert()
    {
        $delete = new Insert();

        die("this is not implemented (insert)");

        return $delete;
    }

}