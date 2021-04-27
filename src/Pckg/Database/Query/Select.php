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

    /**
     * @var bool
     */
    protected $count = false;

    /**
     * @var bool
     */
    protected $distinct = false;

    /**
     * @var null
     */
    protected $countRow = null;

    /**
     * @var null
     */
    protected $lock = null;

    /**
     *
     */
    public function lock()
    {
        $this->lock = 'LOCK IN SHARE MODE';
    }

    /**
     * @param bool $set
     *
     * @return $this
     */
    public function distinct($set = true)
    {
        $this->distinct = $set;

        return $this;
    }

    /**
     * @param bool $count
     *
     * @return $this
     */
    public function count($count = true)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @param $row
     *
     * @return $this
     */
    public function countRow($row)
    {
        $this->countRow = $row;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCounted()
    {
        return $this->count;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        $alias = $this->alias ?? $this->table;

        if (!in_array('`' . $table . '`.*', $this->select) && !in_array('`' . $alias . '`.*', $this->select)) {
            $this->select[] = '`' . $table . '`.*';
        }

        return $this;
    }

    /**
     * @param $alias
     *
     * @return $this
     */
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
    public function buildSQL()
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
            $parts[] = 'ORDER BY ' . ($this->orderBy == 'id' ? '`' . $this->table . "`.`" . $this->orderBy .
                                                               '` ASC' : $this->orderBy);
        }

        if ($this->limit) {
            $parts[] = 'LIMIT ' . $this->limit;
        }

        if ($this->lock) {
            $parts[] = $this->lock;
        }

        $sql = implode(' ', $parts);

        if ($this->diebug) {
            $d = $this->diebug;
            if (is_only_callable($d)) {
                $d($sql, $this->bind);
            } else {
                ddd($sql, $this->bind);
            }
        } elseif ($this->debug) {
            $d = $this->debug;
            if (is_only_callable($d)) {
                $d($sql, $this->bind);
            } else {
                d($sql, $this->bind);
            }
        }

        return $this->getDriver()->recapsulate($sql, '`');
    }

    /**
     * @return string
     */
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
            if ($key && is_string($key)) {
                $keys[] = $select . ' AS `' . $key . '`';
            } else {
                $keys[] = $select;
            }
        }

        return ($this->count ? $this->getDriver()->addFullCount() : '') .
               ($this->distinct ? 'DISTINCT ' : '') .
               ($this->countRow ? 'COUNT(' . $this->countRow . ') AS `count`' : '') .
               ($this->countRow && $keys && $this->groupBy ? ', ' : '') .
               ($this->countRow && !$this->groupBy ? '' : implode(', ', $keys));
    }

    /**
     * @return string
     */
    public function buildTable()
    {
        $alias = $this->alias ?? $this->table;

        return '`' . $this->table . '` AS `' . $alias . '`';
    }

    /**
     * @return array
     */
    public function buildBinds()
    {
        return $this->getBinds(['select', 'from', 'join', 'where', 'having', 'group', 'order', 'limit']);
    }

    /**
     * @param       $fields
     * @param array $bind
     *
     * @return $this
     */
    public function select($fields, $bind = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->select = $fields;

        $this->bind($bind, 'select');

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

    /**
     * @return Delete
     */
    public function transformToDelete()
    {
        $delete = new Delete();
        $delete->setDriver($this->getDriver());

        $delete->setTable($this->table);
        $delete->getWhere()->setChildren($this->where->getChildren());
        $delete->setBind(['where' => $this->getBinds('where')]);
        foreach ($this->join as $join) {
            $delete->join($join);
        }
        $order = $this->getOrderBy();
        if ($order) {
            $delete->orderBy($order);
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

    /**
     * @return Update
     */
    public function transformToUpdate()
    {
        $update = new Update();
        $update->setDriver($this->getDriver());
        $update->setTable($this->table);
        $update->getWhere()->setChildren($this->where->getChildren());
        $update->setBind(['where' => $this->getBinds('where')]);
        $update->debug($this->debug);
        $update->diebug($this->diebug);

        return $update;
    }

    /**
     * @param Select $query
     *
     * @return $this
     */
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

    /**
     * @return array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function prependSelect($fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $collection = collect($this->select);
        foreach ($fields as $key => $field) {
            $collection->prepend($field, is_string($key) ? $key : null);
        }

        $this->select = $collection->all();

        return $this;
    }
}
