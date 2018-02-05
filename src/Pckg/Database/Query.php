<?php namespace Pckg\Database;

use ArrayAccess;
use Exception;
use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Query\Raw;
use Throwable;

/**
 * Class Query
 *
 * @package Pckg\Database
 */
abstract class Query
{

    /**
     *
     */
    const LIKE = 'LIKE';

    /**
     *
     */
    const IN = 'IN';

    /**
     *
     */
    const NOT_LIKE = 'NOT LIKE';

    /**
     *
     */
    const NOT_IN = 'NOT IN';

    /**
     * @var
     */
    protected $table;

    /**
     * @var
     */
    protected $alias;

    /**
     * @var array
     */
    protected $join = [];

    /**
     * @var $this
     */
    protected $where;

    /**
     * @var
     */
    protected $groupBy;

    /**
     * @var $this
     */
    protected $having;

    /**
     * @var
     */
    protected $orderBy;

    /**
     * @var
     */
    protected $limit;

    /**
     * @var
     */
    protected $sql;

    /**
     * @var array
     */
    protected $bind = [];

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected $diebug = false;

    /**
     * Query constructor.
     */
    public function __construct()
    {
        $this->where = (new Parenthesis())->setGlue('AND');
        $this->having = (new Parenthesis())->setGlue('AND');
    }

    /**
     * @param        $sql
     * @param array  $binds
     * @param string $part
     *
     * @return static
     */
    public static function raw($sql, $binds = [], $part = 'main')
    {
        $query = new static($sql);

        if (!is_array($binds)) {
            $binds = [$binds];
        }

        foreach ($binds as $bind) {
            $query->bind($bind, $part);
        }

        return $query;
    }

    /**
     * @param $val
     * @param $part
     *
     * @return $this
     */
    public function bind($val, $part)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        foreach ($val as $v) {
            $this->bind[$part][] = $v;
        }

        return $this;
    }

    /**
     *
     */
    public function __clone()
    {
        $this->where = clone $this->where;
        $this->having = clone $this->having;
    }

    /**
     * @return Raw
     */
    public function toRaw()
    {
        return new Raw('(' . $this->buildSQL() . ')', $this->buildBinds());
    }

    /**
     * @return mixed
     */
    abstract public function buildSQL();

    /**
     * @return mixed
     */
    abstract public function buildBinds();

    /**
     * @param bool $debug
     *
     * @return $this
     */
    public function debug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param bool $diebug
     *
     * @return $this
     */
    public function diebug($diebug = true)
    {
        $this->diebug = $diebug;

        return $this;
    }

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->bind;
    }

    /**
     * @param $bind
     *
     * @return $this
     */
    public function setBind($bind)
    {
        $this->bind = $bind;

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param $alias
     *
     * @return $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return $this
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @return string
     */
    public function buildJoin()
    {
        return implode(" ", $this->join);
    }

    /**
     * @return string
     */
    public function buildWhere()
    {
        return $this->where->hasChildren() ? 'WHERE ' . $this->where->build() : '';
    }

    /**
     * @return string
     */
    public function buildHaving()
    {
        return $this->having->hasChildren() ? 'HAVING ' . $this->having->build() : '';
    }

    /**
     * @param        $key
     * @param bool   $value
     * @param string $operator
     *
     * @return Query
     */
    public function having($key, $value = true, $operator = '=')
    {
        return $this->addCondition($key, $value, $operator, 'having');
    }

    /**
     * @param        $key
     * @param bool   $value
     * @param string $operator
     * @param        $part
     *
     * @return $this
     */
    private function addCondition($key, $value = true, $operator = '=', $part)
    {
        if (is_object($key)) {
            if ($key instanceof Raw) {
                $sql = $key->buildSQL();
                $this->{$part}->push($sql);
                if ($binds = $key->buildBinds()) {
                    if (!is_array($binds)) {
                        $binds = [$binds];
                    }
                    foreach ($binds as $bind) {
                        $this->bind($bind, $part);
                    }
                }

                return $this;
            } elseif ($key instanceof Parenthesis) {
                $sql = $key->build();
                if ($sql) {
                    $sql = '(' . $sql . ')';
                }
                $this->{$part}->push($sql);

                if ($binds = $key->getBinds()) {
                    foreach ($binds as $bind) {
                        $this->bind($bind, $part);
                    }
                }

                return $this;
            }
        }
        if (is_object($value) && object_implements($value, ArrayAccess::class)) {
            $value = $value->__toArray();
        }

        if (is_array($value)) {
            if (count($value) == 0) {
                $value = null;
            } else if (count($value) == 1) {
                $value = end($value);
            }
        }

        if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = null;
        }

        $hasValue = $value || (is_scalar($value) && strlen($value)) || (is_array($value) && count($value));

        if (!is_array($value) && !is_object($value) && in_array($operator, ['IN', 'NOT IN'])) {
            $value = [$value];
        }

        if (is_array($value) && $operator == '=') {
            $operator = 'IN';
        } else if (is_object($value) && $value instanceof Raw && $operator == '=') {
            $operator = 'IN';
        } else if (is_object($value) && $value instanceof Entity && !in_array($operator, ['IN', 'NOT IN'])) {
            $operator = 'IN';
        }

        if (is_only_callable($key)) {
            $key($this->{$part});
        } else if ($operator == 'IN' || $operator == 'NOT IN') {
            if (is_array($value)) {
                if (!$hasValue) {
                    /**
                     * This is probable not needed.
                     */
                    // $this->where->push($this->makeKey($key));
                    $this->{$part}->push($operator == 'IN' ? '0 = 1' : '1 = 1');
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
                    if (!is_array($binds)) {
                        $binds = [$binds];
                    }
                    foreach ($binds as $bind) {
                        $this->bind($bind, $part);
                    }
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
                ) . ($hasValue ? ($value === true ? '' : ' ' . $operator . ' ?') : ' ' . $operator . ' NULL')
            );
            if ($hasValue && $value !== true) {
                $this->bind($value, 'where');
            }
        } elseif ($operator == 'LIKE' || $operator == 'NOT LIKE') {
            $this->{$part}->push($this->makeKey($key) . ' ' . $operator . ' ?');
            $this->bind($value, $part);
        } elseif ($operator == 'BETWEEN') {
            $this->{$part}->push($this->makeKey($key) . ' ' . $operator . ' ? AND ?');
            $this->bind($value, $part);
        } else {
            $valuePrefix = $value === true ? '' : ' ';
            $valueSuffix = !$hasValue && $operator
                ? (in_array($operator, ['IS NULL', 'IS NOT NULL'])
                    ? ' ' . $operator
                    : ' IS NULL')
                : '';
            $operatorSql = $operator && (($value || strlen($value)) && $value !== true)
                ? $operator . ' ?'
                : '';
            $suffix = $hasValue
                ? $valuePrefix . $operatorSql
                : $valueSuffix;
            $this->{$part}->push($this->makeKey($key) . $suffix);
            if ($hasValue && $value !== true) {
                $this->bind($value, $part);
            }
        }

        return $this;
    }

    /**
     * @param $key
     *
     * @return int|string
     */
    private function makeKey($key)
    {
        return is_numeric($key) || strpos($key, '`') !== false || strpos($key, ' ') !== false || strpos($key, '.') ||
               strpos($key, ',') || strpos($key, '(')
            ? $key
            : '`' . $key . '`';
    }

    /**
     * @return Parenthesis
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * @param        $key
     * @param bool   $value
     * @param string $operator
     *
     * @return Query
     */
    public function orWhere($key, $value = true, $operator = '=')
    {
        $this->where->setGlue('OR');

        return $this->where($key, $value, $operator);
    }

    /**
     * @param        $key
     * @param bool   $value
     * @param string $operator
     *
     * @return Query
     */
    public function where($key, $value = true, $operator = '=')
    {
        return $this->addCondition($key, $value, $operator, 'where');
    }

    /**
     * @param $groupBy
     *
     * @return $this
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    /**
     * @param $groupBy
     *
     * @return $this
     */
    public function addGroupBy($groupBy)
    {
        if ($this->groupBy) {
            $this->groupBy .= ', ';
        }

        $this->groupBy .= $groupBy;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param $orderBy
     *
     * @return $this
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param array $parts
     * @param bool  $clear
     *
     * @return array
     */
    public function getBinds($parts = [], $clear = false)
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

            foreach ($this->bind[$part] as $key => $bind) {
                $binds[] = $bind;
                if ($clear) {
                    unset($this->bind[$part][$key]);
                }
            }
        }

        return $binds;
    }

    /**
     * @param       $table
     * @param null  $on
     * @param null  $where
     * @param array $binds
     *
     * @return $this
     */
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

    /**
     * @return array
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     *
     */
    public function makeJoinsLeft()
    {
        foreach ($this->join as &$join) {
            $join = str_replace('INNER JOIN', 'LEFT JOIN', $join);
        }
    }

    /**
     * @param Entity $entity
     * @param        $data
     * @param        $table
     *
     * @throws Exception
     */
    public function primaryWhere(Entity $entity, $data, $table)
    {
        $primaryKeys = $entity->getRepository()->getCache()->getTablePrimaryKeys($table);

        if (!$primaryKeys) {
            throw new Exception('Primary key must be set on deletion!');
        }

        if (strpos($table, '_i18n')) {
            $primaryKeys = ['id', 'language_id'];
        } elseif (strpos($table, '_p17n')) {
            $primaryKeys = ['id', 'user_group_id'];
        }

        foreach ($primaryKeys as $primaryKey) {
            $this->where('`' . $primaryKey . '`', $data[$primaryKey]);
        }
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function __toString()
    {
        try {
            return (string)$this->buildSQL();
        } catch (Throwable $e) {
            if (dev()) {
                dd('query', $e->getMessage(), $e->getFile(), $e->getLine());
            }

            throw $e;
        }
    }

}