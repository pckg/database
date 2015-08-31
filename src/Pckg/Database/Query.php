<?php

namespace Pckg\Database;

/**
 * Class Query
 * @package Pckg\Database
 */
class Query
{

    /**
     * @var
     */
    /**
     * @var
     */
    /**
     * @var
     */
    /**
     * @var
     */
    /**
     * @var
     */
    /**
     * @var
     */
    /**
     * @var
     */
    protected $table, $join, $where, $groupBy, $having, $orderBy, $limit;

    protected $sql;

    protected $bind = [];

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
        return "'" . $value . "'";
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
     *
     */
    function __construct($sql = null)
    {
        $this->sql = $sql;
    }

    /**
     *
     */
    public function buildSQL()
    {
        return null;
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
        return $this->where ? ' WHERE ' . implode(" AND ", $this->where) : '';
    }

    /**
     * @return string
     */
    function buildHaving()
    {
        return $this->having ? ' HAVING ' . implode(" AND ", $this->having) : '';
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
    function where($key, $value, $operator = '=')
    {
        if ($operator == 'IN') {
            if (is_array($value)) {
                $this->where[] = $key . ' IN(' . implode(',', static::escapeArray($value)) . ')';

            } else if ($value instanceof Query) {
                $this->where[] = $key . ' IN(' . $value->buildSQL() . ')';

            }

        } else {
            $this->where[] = $key . ' ' . $operator . ' ' . static::escape($value);

        }

        return $this;
    }

    function whereOr($key, $value, $operator = '=')
    {

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