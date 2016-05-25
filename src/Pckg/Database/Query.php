<?php

namespace Pckg\Database;

use Pckg\Database\Query\Parenthesis;

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

    public function table($table)
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

    public function buildJoin()
    {
        return implode(" ", $this->join);
    }

    public function buildWhere()
    {
        return $this->where->hasChildren() ? ' WHERE ' . $this->where->build() : '';
    }

    public function buildHaving()
    {
        return $this->having->hasChildren() ? ' HAVING ' . $this->having->build() : '';
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

    public function orWhere($key, $value = true, $operator = '=')
    {
        $this->where->setGlue('OR');

        return $this->where($key, $value, $operator);
    }

    public function where($key, $value = true, $operator = '=')
    {
        if (is_array($value)) {
            $operator = 'IN';
        }

        if (is_callable($key)) {
            $key($this->where);

        } else if ($operator == 'IN') {
            if (is_array($value)) {
                if (!$value) {
                    $this->where->push($this->makeKey($key));
                    $this->where->push('0 = 1');
                } else {
                    $this->where->push($this->makeKey($key) . ' IN(' . str_repeat('?, ', count($value) - 1) . '?)');
                    $this->bind($value, 'where');
                }

            } else if ($value instanceof Query) {
                $this->where->push($this->makeKey($key) . ' IN(' . $value->buildSQL() . ')');
                $this->bind($value->buildBinds(), 'where');

            }

        } else {
            $this->where->push($this->makeKey($key) . ($value ? ($value === true ? '' : ' ' . $operator . ' ?') : ' IS NULL'));
            if ($value && $value !== true) {
                $this->bind($value, 'where');
            }
        }

        return $this;
    }

    private function makeKey($key)
    {
        return is_numeric($key) ? $key : '`' . $key . '`';
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

        if (!$parts) {
            foreach ($this->bind as $parts) {
                foreach ($parts as $bind) {
                    $binds[] = $bind;
                }
            }
        }

        return $binds;
    }

    public function join($table, $on = null, $where = null)
    {
        $this->join[] = $table;

        return $this;
    }

    public function primaryWhere(Entity $entity, $data, $table)
    {
        foreach ($entity->getRepository()->getCache()->getTablePrimaryKeys($table) as $primaryKey) {
            $this->where($primaryKey, $data[$primaryKey]);
        }
    }

    abstract public function buildSQL();

    abstract public function buildBinds();

    public function __toString()
    {
        try {
            return $this->buildSQL();
        } catch (\Exception $e) {
            dd('query', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

}