<?php

namespace Pckg\Database;

use Pckg\Database\Query\Parenthesis;

/**
 * Class Query
 * @package Pckg\Database
 */
abstract class Query
{

    protected $table, $join, $where, $groupBy, $having, $orderBy, $limit;

    protected $sql;

    protected $bind = [];

    public function __construct()
    {
        $this->where = (new Parenthesis())->setGlue('AND');
        $this->having = (new Parenthesis())->setGlue('AND');
    }

    public static function raw($sql)
    {
        $query = new static($sql);

        return $query;
    }

    public function getBind()
    {
        return $this->bind;
    }

    public function __toString()
    {
        try {
            return $this->buildSQL()['sql'];
        } catch (\Exception $e) {
            dd('query', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    abstract function buildSQL();

    abstract function buildBinds();

    /**
     * @param $table
     *
     * @return $this
     */
    function table($table)
    {
        $this->table = $table;

        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    function buildJoin()
    {
        return implode(" ", $this->join);
    }

    /**
     * @return string
     */
    function buildWhere()
    {
        return $this->where->hasChildren() ? ' WHERE ' . $this->where->build() : '';
    }

    /**
     * @return string
     */
    function buildHaving()
    {
        return $this->having->hasChildren() ? ' HAVING ' . $this->having->build() : '';
    }

    /**
     * @param $orderBy
     *
     * @return $this
     */
    function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    function orWhere($key, $value = null, $operator = '=')
    {
        $this->where->setGlue('OR');

        return $this->where($key, $value, $operator);
    }

    /**
     * @param $where
     *
     * @return $this
     */
    function where($key, $value = null, $operator = '=')
    {
        if (is_callable($key)) {
            $key($this->where);

        } else if ($operator == 'IN') {
            if (is_array($value)) {
                $this->where->push($this->makeKey($key) . ' IN(' . str_repeat('?, ', count($value) - 1) . '?)');
                $this->bind($value, 'where');

            } else if ($value instanceof Query) {
                $this->where->push($this->makeKey($key) . ' IN(' . $value->buildSQL()['sql'] . ')');

            }

        } else {
            $this->where->push($this->makeKey($key) . ' ' . $operator . ' ?');
            $this->bind($value, 'where');

        }

        return $this;
    }

    private function makeKey($key)
    {
        return '`' . $key . '`';
    }

    public function bind($val, $part)
    {
        $this->bind[$part][] = $val;

        return $this;
    }

    public function getBinds($parts = [])
    {
        $binds = [];
        foreach ($parts as $part) {
            if (isset($this->bind[$part])) {
                foreach ($this->bind[$part] as $bind) {
                    $binds[] = $bind;
                }
            }
        }

        return $binds;
    }

    /**
     * @param $join
     *
     * @return $this
     */
    function join($table, $on = null, $where = null)
    {
        $this->join[] = $table;

        return $this;
    }
}