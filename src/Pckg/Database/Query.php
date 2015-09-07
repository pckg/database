<?php

namespace Pckg\Database;
use Pckg\Database\Query\Parenthesis;

/**
 * Class Query
 * @package Pckg\Database
 */
class Query
{

    protected $table, $join, $where, $groupBy, $having, $orderBy, $limit;

    protected $sql;

    protected $bind = [];

    public function __construct()
    {
        $this->where = (new Parenthesis())->setGlue('AND');
        $this->having = (new Parenthesis())->setGlue('AND');
    }

    public function getBind()
    {
        return $this->bind;
    }

    public static function raw($sql)
    {
        $query = new static($sql);

        return $query;
    }

    public static function escape($value)
    {
        return is_numeric($value)
            ? $value
            : (is_bool($value)
                ? ($value
                    ? 1
                    : 'NULL'
                )
                : "'" . $value . "'"
            );
    }

    public static function escapeArray($array)
    {
        foreach ($array as &$value) {
            $value = static::escape($value);
        }

        return $array;
    }

    public function __toString()
    {
        return (string)$this->buildSQL();
    }

    /**
     * @param $table
     * @return $this
     */
    function table($table)
    {
        $this->table = $table;

        return $this;
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
     * @return $this
     */
    function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $where
     * @return $this
     */
    function where($key, $value = null, $operator = '=')
    {
        if (is_callable($key)) {
            $key($this->where);

        } else if ($operator == 'IN') {
            if (is_array($value)) {
                $this->where->push($this->makeKey($key) . ' IN(' . implode(',', static::escapeArray($value)) . ')');

            } else if ($value instanceof Query) {
                $this->where->push($this->makeKey($key) . ' IN(' . $value->buildSQL() . ')');

            }

        } else {
            $this->where->push($this->makeKey($key) . ' ' . $operator . ' ' . static::escape($value));

        }

        return $this;
    }

    private function makeKey($key)
    {
        return '`' . $key . '`';
    }

    function orWhere($key, $value = null, $operator = '=')
    {
        $this->where->setGlue('OR');

        return $this->where($key, $value, $operator);
    }

    /**
     * @param $join
     * @return $this
     */
    function join($join, $on = null, $where = null)
    {
        $this->join[] = $join;

        return $this;
    }
}