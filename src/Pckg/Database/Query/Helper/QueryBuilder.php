<?php

namespace Pckg\Database\Query\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Query;
use Pckg\Database\Query\Select;
use Pckg\Database\Relation;

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

    protected $prevQuery;

    public function debug($debug = true)
    {
        $this->getQuery()->debug($debug);

        return $this;
    }

    public function diebug($debug = true)
    {
        $this->getQuery()->diebug($debug);

        return $this;
    }

    /**
     * @return Query|Select
     */
    public function getQuery()
    {
        return $this->query
            ? $this->query
            : $this->resetQuery()->getQuery();
    }

    public function getPrevQuery()
    {
        return $this->prevQuery;
    }

    public function resetQuery()
    {
        $this->prevQuery = $this->query;

        $this->query = (new Select());

        if (isset($this->table)) {
            $this->query->table($this->table);
        }

        if (isset($this->alias)) {
            $this->query->alias($this->alias);
        }

        return $this;
    }

    public function resetRelations()
    {
        $this->with = [];
    }

    public function toRaw()
    {
        return $this->query->toRaw();
    }

    public function setQuery($query)
    {
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
    public function join($table, $on = null, $where = null)
    {
        if ($table instanceof Relation) {
            if (is_only_callable($on)) {
                /**
                 * Is this needed?
                 */
                Reflect::call($on, [$table, $table->getQuery()]);
            }

            $table->mergeToQuery($this->getQuery());
        } elseif ($table instanceof Entity) {
            $query = $table->getQuery();

            $this->getQuery()->join(
                'LEFT JOIN (' . $query->buildSQL() . ') AS `' . $where . '` ON ' . $where . '.' . $on,
                null,
                null,
                $query->buildBinds()
            );
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
        if ((isset($this->table) || isset($this->alias)) && is_string($key)
            && strpos($key, '.') === false && strpos($key, '`') === false && strpos($key, ' ') === false
        ) {
            if ($this->alias) {
                $key = '`' . $this->alias . '`.`' . $key . '`';
            } else {
                $key = '`' . $this->table . '`.`' . $key . '`';
            }
        }

        $this->getQuery()->where($key, $value, $operator);

        return $this;
    }

    public function whereRaw($raw, $bind = [])
    {
        $this->getQuery()->where(Query\Raw::raw($raw), $bind);

        return $this;
    }

    public function whereArr($data, $operator = '=')
    {
        foreach ($data as $key => $value) {
            $this->where($key, $value, $operator);
        }

        return $this;
    }

    public function orWhere($key, $value = true, $operator = '=')
    {
        $this->getQuery()->getWhere()->setGlue('OR');

        $this->where($key, $value, $operator);

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
        if ($this instanceof Entity) {
            $key = $this->extendedKey($key);
        }

        $this->getQuery()->orderBy($key);

        return $this;
    }

    public function limit($limit)
    {
        $this->getQuery()->limit($limit);

        return $this;
    }

    public function count($count = true)
    {
        $this->getQuery()->count($count);

        return $this;
    }

    public function countRow($row = '*')
    {
        $this->getQuery()->countRow($row);

        return $this;
    }

    public function addSelect($fields = [])
    {
        $this->getQuery()->addSelect($fields);

        return $this;
    }

    public function prependSelect($fields = [])
    {
        $this->getQuery()->prependSelect($fields);

        return $this;
    }

    public function select($fields = [])
    {
        $this->getQuery()->select($fields);

        return $this;
    }

    public function selectCount($as = 'count', $what = '*')
    {
        $this->getQuery()->select([$as => 'COUNT(' . $what . ')']);

        return $this;
    }

    public function addCount($as = 'count', $what = '*')
    {
        $this->getQuery()->addSelect([$as => 'COUNT(' . $what . ')']);

        return $this;
    }

    public function getSelect()
    {
        return $this->getQuery()->getSelect();
    }

}