<?php

namespace Pckg\Database\Query;

use Pckg\Database\Entity;
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

    protected $countRow = null;

    public function count($count = true)
    {
        $this->count = $count;

        return $this;
    }

    public function countRow($row)
    {
        $this->countRow = $row;

        return $this;
    }

    public function isCounted()
    {
        return $this->count;
    }

    public function table($table)
    {
        $this->table = $table;
        $alias = $this->alias ?? $this->table;

        if (!in_array('`' . $table . '`.*', $this->select) && !in_array('`' . $alias . '`.*', $this->select)) {
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

        if ($this->diebug) {
            dd($sql, $this->bind);
        } elseif ($this->debug) {
            d($sql, $this->bind);
        }

        return $sql;
    }

    public function buildSelect()
    {
        if (!$this->select) {
            $this->select[] = $this->table . '.*';
        }

        $keys = [];
        foreach ($this->select as $key => $select) {
            if ($select instanceof Query) {
                foreach ($select->getBinds('select') as $bind) {
                    // @changed
                    $this->bind($bind, 'select');
                }
            }
            if (is_numeric($key)) {
                $keys[] = $select;

            } else {
                $keys[] = $select . ' AS `' . $key . '`';

            }
        }

        return ($this->count ? 'SQL_CALC_FOUND_ROWS ' : '') .
               ($this->countRow ? 'COUNT(' . $this->countRow . ') AS `count`' : '') .
               ($this->countRow && $keys && $this->groupBy ? ', ' : '') .
               ($this->countRow && !$this->groupBy ? '' : implode(', ', $keys));
    }

    public function buildTable()
    {
        $alias = $this->alias ?? $this->table;

        return '`' . $this->table . '` AS `' . $alias . '`';
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
    public function addSelect($fields, $bind = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $key => $field) {
            if ($field instanceof Entity) {
                $query = $field->getQuery();
                $this->bind($query->buildBinds(), 'select');
                if (is_numeric($key)) {
                    $this->select[] = '(' . $query->buildSQL() . ')';
                } else {
                    $this->select[$key] = '(' . $query->buildSQL() . ')';
                }
            } elseif ($field instanceof Raw) {
                $this->bind($field->buildBinds(), 'select');
                if (is_numeric($key)) {
                    $this->select[] = $field->buildSQL();
                } else {
                    $this->select[$key] = $field->buildSQL();
                }
            } elseif (is_numeric($key)) {
                $this->select[] = $field;
            } else {
                $this->select[$key] = $field;
            }
        }

        if ($bind) {
            $this->bind($bind, 'select');
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

    public function mergeToQuery(Select $query)
    {
        foreach ($this->getSelect() as $key => $select) {
            $query->prependSelect([$key => $select]);
        }

        foreach ($this->getJoin() as $join) {
            $query->join($join, null, null, $this->getBinds('join'));
        }

        if ($groupBy = $this->getGroupBy()) {
            $query->groupBy($groupBy);
        }

        if (($having = $this->getHaving()) && $having->hasChildren()) {
            $query->having($having);
        }

        if ($orderBy = $this->getOrderBy()) {
            $query->orderBy($orderBy);
        }

        return $this;
    }

}