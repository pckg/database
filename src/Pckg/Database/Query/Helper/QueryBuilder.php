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
     * @return Query|Select
     */
    public function getQuery()
    {
        return $this->query
            ? $this->query
            : $this->resetQuery()->getQuery();
    }

    public function resetQuery()
    {
        $this->query = (new Select())->table($this->table);

        return $this;
    }

    /**
     * @param      $table
     * @param null $on
     * @param null $where
     *
     * @return $this
     */
    public function join($table, $on = null, $where = null)
    {
        if ($table instanceof Relation) {
            $table->mergeToQuery($this->getQuery());

        } else {
            $this->getQuery()->join($table, $on, $where);

        }

        return $this;
    }

    /**
     * @param        $key
     * @param        $value
     * @param string $operator
     *
     * @return $this
     */
    public function where($key, $value = true, $operator = '=')
    {
        if (is_string($key) && !strpos($key, '.')) {
            $key = $this->table . '`.`' . $key;
        }

        $this->getQuery()->where($key, $value, $operator);

        return $this;
    }

    /**
     * @param        $key
     * @param        $value
     * @param string $operator
     *
     * @return $this
     */
    public function having($key, $value = true, $operator = '=')
    {
        $this->getQuery()->having($key, $value, $operator);

        return $this;
    }

    public function groupBy($key)
    {
        $this->getQuery()->groupBy($key);

        return $this;
    }

    public function orderBy($key)
    {
        $this->getQuery()->orderBy($key);

        return $this;
    }

    public function limit($limit)
    {
        $this->getQuery()->limit($limit);

        return $this;
    }

    public function count()
    {
        $this->getQuery()->count();

        return $this;
    }

}