<?php

namespace Pckg\Database;

use ArrayAccess;
use Exception;
use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Query\Raw;

abstract class Query
{

    protected $table, $alias, $join = [], $where, $groupBy, $having, $orderBy, $limit;

    protected $sql;

    protected $bind = [];

    public function __construct()
    {
        $this->where = (new Parenthesis())->setGlue('AND');
        $this->having = (new Parenthesis())->setGlue('AND');
    }

    public function __clone()
    {
        $this->where = clone $this->where;
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

    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function alias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function buildJoin()
    {
        return implode(" ", $this->join);
    }

    public function buildWhere()
    {
        return $this->where->hasChildren() ? 'WHERE ' . $this->where->build() : '';
    }

    public function buildHaving()
    {
        return $this->having->hasChildren() ? 'HAVING ' . $this->having->build() : '';
    }

    public function having($key, $value = true, $operator = '=')
    {
        return $this->addCondition($key, $value, $operator, 'having');
    }

    public function where($key, $value = true, $operator = '=')
    {
        return $this->addCondition($key, $value, $operator, 'where');
    }

    private function addCondition($key, $value = true, $operator = '=', $part)
    {
        if (is_object($key) && $key instanceof Raw) {
            $sql = $key->buildSQL();
            $this->{$part}->push($sql);
            if ($binds = $key->buildBinds()) {
                //$this->bind($binds, 'where');
                foreach ($binds as $bind) {
                    $this->bind($bind, $part);
                }
            }

            return $this;
        }
        if (is_object($value) && object_implements($value, ArrayAccess::class)) {
            $value = $value->__toArray();
        }

        if (is_array($value) && $operator == '=') {
            $operator = 'IN';
        }

        if (is_object($value) && $value instanceof Raw && $operator == '=') {
            $operator = 'IN';
        }

        if (is_callable($key)) {
            $key($this->{$part});

        } else if ($operator == 'IN' || $operator == 'NOT IN') {
            if (is_array($value)) {
                if (!$value) {
                    /**
                     * This is probable not needed.
                     */
                    // $this->where->push($this->makeKey($key));
                    $this->{$part}->push('0 = 1');
                } else {
                    $this->{$part}->push(
                        $this->makeKey($key) . ' ' . $operator . '(' . str_repeat('?, ', count($value) - 1) . '?)'
                    );
                    foreach ($value as $val) {
                        $this->bind($val, $part);
                    }
                }

            } else if ($value instanceof Query) {
                $this->{$part}->push($this->makeKey($key) . ' ' . $operator . '(' . $value->buildSQL() . ')');
                if ($binds = $value->buildBinds()) {
                    $this->bind($binds, $part);
                }

            } else if ($value instanceof Entity) {
                $this->{$part}->push(
                    $this->makeKey($key) . ' ' . $operator . '(' . $value->getQuery()->buildSQL() . ')'
                );
                if ($binds = $value->getQuery()->buildBinds()) {
                    $this->bind($binds, $part);
                }
            }

        } elseif ($operator == 'IS' || $operator == 'IS NOT') {
            $this->{$part}->push(
                $this->makeKey(
                    $key
                ) . ($value ? ($value === true ? '' : ' ' . $operator . ' ?') : ' ' . $operator . ' NULL')
            );
            if ($value && $value !== true) {
                $this->bind($value, 'where');
            }
        } elseif ($operator == 'LIKE' || $operator == 'NOT LIKE') {
            $this->{$part}->push($this->makeKey($key) . ' ' . $operator . ' ?');
            $this->bind($value, $part);
        } else {
            $this->{$part}->push(
                $this->makeKey($key) . ($value ? ($value === true ? '' : ' ' . $operator . ' ?') : ' IS NULL')
            );
            if ($value && $value !== true) {
                $this->bind($value, $part);
            }
        }

        return $this;
    }

    public function orWhere($key, $value = true, $operator = '=')
    {
        $this->where->setGlue('OR');

        return $this->where($key, $value, $operator);
    }

    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    private function makeKey($key)
    {
        return is_numeric($key) || strpos($key, '`') !== false || strpos($key, '.') ? $key : '`' . $key . '`';
    }

    public function bind($val, $part)
    {
        $this->bind[$part][] = $val;

        return $this;
    }

    public function setBind($bind)
    {
        $this->bind = $bind;

        return $this;
    }

    public function getBinds($parts = [])
    {
        $binds = [];

        if (!is_array($parts)) {
            $parts = [$parts];
        }

        if (!$parts) {
            $parts = array_keys($this->bind);
        }

        foreach ($parts as $part) {
            if (!isset($this->bind[$part])) {
                continue;
            }

            foreach ($this->bind[$part] as $bind) {
                $binds[] = $bind;
            }
        }

        if ($binds && is_array($binds[0])) {
            dd($this, $binds);
        }

        return $binds;
    }

    public function join($table, $on = null, $where = null, $binds = [])
    {
        if (!$on) {
            $this->join[] = $table;
        } else {
            $this->join[] = $table . (strpos($table, ' ON ') ? ' AND ' : ' ON ') . $on;
        }

        if ($where) {
            $this->where(new Raw($where));
        }

        if ($binds) {
            foreach ($binds as $bind) {
                $this->bind($bind, 'join');
            }
        }

        return $this;
    }

    public function getJoin()
    {
        return $this->join;
    }

    public function makeJoinsLeft()
    {
        foreach ($this->join as &$join) {
            $join = str_replace('INNER JOIN', 'LEFT JOIN', $join);
        }
    }

    public function primaryWhere(Entity $entity, $data, $table)
    {
        $primaryKeys = $entity->getRepository()->getCache()->getTablePrimaryKeys($table);

        if (!$primaryKeys) {
            throw new Exception('Primary key must be set on deletion!');
        }

        foreach ($primaryKeys as $primaryKey) {
            $this->where('`' . $primaryKey . '`', $data[$primaryKey]);
        }
    }

    abstract public function buildSQL();

    abstract public function buildBinds();

    public function __toString()
    {
        try {
            return $this->buildSQL();
        } catch (Exception $e) {
            dd('query', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

}