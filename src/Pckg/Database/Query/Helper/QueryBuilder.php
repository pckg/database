<?php

namespace Pckg\Database\Query\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Query;
use Pckg\Database\Query\Raw;
use Pckg\Database\Query\Select;
use Pckg\Database\Relation;
use Pckg\Database\Relation\HasAndBelongsTo;

/**
 * Class QueryBuilder
 *
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
    public function getQuery() {
        return $this->query
            ? $this->query
            : $this->resetQuery()->getQuery();
    }

    public function resetQuery() {
        $this->query = (new Select());

        if (isset($this->table)) {
            $this->query->table($this->table);
        }

        return $this;
    }

    public function setQuery($query) {
        $this->query = $query;

        return $this;
    }

    /**
     * @param      $table
     * @param null $on
     * @param null $where
     *
     * @return $this
     */
    public function join($table, $on = null, $where = null) {
        if ($table instanceof Relation) {
            if (is_callable($on)) {
                $on($this->getQuery());
            }
            
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
    public function where($key, $value = true, $operator = '=') {
        if ((isset($this->table) || isset($this->alias)) && is_string($key) && !strpos($key, '.') && strpos($key, '`') === false) {
            if ($this->alias) {
                $key = '`' . $this->alias . '`.`' . $key . '`';
            } else {
                $key = '`' . $this->table . '`.`' . $key . '`';
            }
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
    public function having($key, $value = true, $operator = '=') {
        $this->getQuery()->having($key, $value, $operator);

        return $this;
    }

    public function groupBy($key) {
        $this->getQuery()->groupBy($key);

        return $this;
    }

    public function orderBy($key) {
        $this->getQuery()->orderBy($key);

        return $this;
    }

    public function limit($limit) {
        $this->getQuery()->limit($limit);

        return $this;
    }

    public function count($count = true) {
        $this->getQuery()->count($count);

        return $this;
    }

    public function addSelect($fields = []) {
        $this->getQuery()->addSelect($fields);

        return $this;
    }

    public function select($fields = []) {
        $this->getQuery()->select($fields);

        return $this;
    }

    public function getSelect() {
        return $this->getQuery()->getSelect();
    }

}