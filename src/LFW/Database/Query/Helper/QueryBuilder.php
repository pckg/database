<?php

namespace LFW\Database\Query\Helper;

use LFW\Database\Query;
use LFW\Database\Query\Select;
use LFW\Database\Relation;

/**
 * Class QueryBuilder
 * @package LFW\Database\Query\Helper
 */
trait QueryBuilder
{

    /**
     * @var
     */
    protected $query;

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query
            ? $this->query
            : ($this->query = (new Select())->table($this->table));
    }

    /**
     * @param $table
     * @param null $on
     * @param null $where
     * @return $this
     */
    public function join($table, $on = null, $where = null)
    {
        $this->getQuery()->join($table, $on, $where);

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param string $operator
     * @return $this
     */
    public function where($key, $value, $operator = '=')
    {
        $this->getQuery()->where($key, $value, $operator);

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param string $operator
     * @return $this
     */
    public function having($key, $value, $operator = '=')
    {
        $this->getQuery()->having($key, $value, $operator);

        return $this;
    }

}