<?php

namespace Pckg\Database\Query\Helper;

use Pckg\Database\Query;
use Pckg\Database\Query\Select;
use Pckg\Database\Relation;

/**
 * Class QueryBuilder
 * @package Pckg\Database\Query\Helper
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
            : $this->resetQuery()->getQuery();
    }

    public function resetQuery() {
        $this->query = (new Select())->table($this->table);

        return $this;
    }

    /**
     * @param $table
     * @param null $on
     * @param null $where
     * @return $this
     */
    public function join($table, $on = null, $where = null)
    {
        if ($table instanceof Relation) {
        } else {
            $this->getQuery()->join($table, $on, $where);
        }

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