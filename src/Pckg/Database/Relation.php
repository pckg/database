<?php

namespace Pckg\Database;

use Pckg\Concept\Reflect;
use Pckg\Database\Query\Helper\QueryBuilder;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Query\Select;
use Pckg\Database\Relation\Helper\RightEntity;

/**
 * Class Relation
 *
 * @package Pckg\Database
 */
abstract class Relation implements RelationInterface
{
    use With;
    use RightEntity;
    use QueryBuilder;

    /**
     *
     */
    const LEFT_JOIN = 'LEFT JOIN';

    /**
     *
     */
    const LEFT_OUTER_JOIN = 'LEFT OUTER JOIN';

    /**
     *
     */
    const RIGHT_JOIN = 'RIGHT JOIN';

    /**
     *
     */
    const INNER_JOIN = 'INNER JOIN';

    /**
     * @var string
     */
    public $join = self::INNER_JOIN;

    /**
     * @var
     */
    protected $left;

    /**
     * @var
     */
    protected $on;

    /**
     * @var
     */
    protected $onAdditional;

    /**
     * @var
     */
    protected $record;

    /**
     * @var
     */
    protected $fill;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var
     */
    protected $primaryCollectionKey;

    /**
     * @var
     */
    protected $foreignKey;

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var Select
     */
    protected $query;

    /**
     * @var
     */
    protected $after;

    /**
     * @var callable|null
     */
    protected $filterLeft;

    /**
     * @param $left
     * @param $right
     */
    public function __construct($left, $right)
    {
        $this->left = $left;
        $this->right = $right;
        $this->fill = $this->getCalee();
    }

    public function __clone()
    {
        /**
         * Clone only right entity.
         */
        if (is_object($this->right)) {
            $this->right = clone $this->right;
        }
    }

    /**
     * @param int $depth
     *
     * @return mixed
     */
    protected function getCalee($depth = 4)
    {
        if (debug_backtrace()[$depth]['function'] == 'hasMany') {
            return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[$depth + 1]['function'];
        }

        return debug_backtrace()[$depth]['function'];
    }

    /**
     * @param $method
     * @param $args
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        if (method_exists($this->getQuery(), $method)) {
            /**
             * First overload Query.
             *
             * @T00D00 - why is this needed?
             */
            Reflect::method($this->getQuery(), $method, $args);
        } elseif (method_exists($this->getRightEntity(), $method)) {
            /**
             * Then right entity.
             */
            Reflect::method($this->getRightEntity(), $method, $args);
        } else {
            $this->callWith($method, $args, $this->getRightEntity());
        }

        return $this;
    }

    /**
     * @param $primaryKey
     *
     * @return $this
     */
    public function primaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @param $foreignKey
     *
     * @return $this
     */
    public function foreignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @return $this
     */
    public function leftJoin()
    {
        $this->join = static::LEFT_JOIN;

        return $this;
    }

    /**
     * @return $this
     */
    public function leftOuterJoin()
    {
        $this->join = static::LEFT_OUTER_JOIN;

        return $this;
    }

    /**
     * @return $this
     */
    public function innerJoin()
    {
        $this->join = static::INNER_JOIN;

        return $this;
    }

    /**
     * @param $fill
     *
     * @return $this
     */
    public function fill($fill)
    {
        $this->fill = $fill;

        return $this;
    }

    /**
     * @param $after
     *
     * @return $this
     */
    public function after($after)
    {
        $this->after = $after;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFill()
    {
        return $this->fill;
    }

    public function getTable()
    {
        return $this->getLeftEntity()->getTable();
    }

    public function getRepository()
    {
        return $this->getLeftRepository();
    }

    /**
     * @return Repository
     * @throws \Exception
     */
    public function getLeftRepository()
    {
        return $this->getLeftEntity()->getRepository();
    }

    /**
     * @return Entity
     * @throws \Exception
     */
    public function getLeftEntity()
    {
        return $this->left; // left is always entity
    }

    /**
     * @param Record $record
     *
     * @return $this
     */
    public function onRecord(Record $record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalCondition()
    {
        return $this->onAdditional
            ? ' AND ' . $this->onAdditional
            : '';
    }

    /**
     * @param Select $query
     *
     * @return $this
     */
    public function mergeToQuery(Select $query)
    {
        $query->join(
            $this->getKeyCondition(),
            $this->getQuery()->getWhere()->build(),
            null,
            $this->getQuery()->getBinds('where')
        );

        foreach ($this->select as $select) {
            $query->prependSelect($select);
        }

        foreach ($this->getQuery()->getSelect() as $key => $select) {
            $query->prependSelect([$key => $select]);
        }

        foreach ($this->getQuery()->getJoin() as $join) {
            $query->join($join, null, null, $this->getQuery()->getBinds('join', true));
        }

        if ($groupBy = $this->getQuery()->getGroupBy()) {
            $query->groupBy($groupBy);
        }

        if (($having = $this->getQuery()->getHaving()) && $having->hasChildren()) {
            $query->having($having);
        }

        if ($orderBy = $this->getQuery()->getOrderBy()) {
            $query->orderBy($orderBy);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getKeyCondition()
    {
        $rightEntity = $this->getRightEntity();
        $leftEntity = $this->getLeftEntity();
        $rightAlias = $rightEntity->getAlias() ?? $rightEntity->getTable();
        $leftAlias = $leftEntity->getAlias() ?? $leftEntity->getTable();

        $condition = $this->join . ' `' . $rightEntity->getTable() . '` AS `' . $rightAlias . '`' .
                     ($this->primaryKey && $this->foreignKey
                         ? ' ON `' . $leftAlias . '`.`' . $this->primaryKey . '`' .
                           ' = `' . $rightAlias . '`.`' . $this->foreignKey . '`'
                         : '');

        return $condition;
    }

    /**
     * @param callable $callable
     * @param          $entity
     * @param null     $query
     */
    public function reflect(callable $callable, $entity, $query = null)
    {
        Reflect::call(
            $callable,
            [
                $query ?? $this->getQuery(),
                $this,
                $entity,
            ]
        );
    }

    /**
     * @param callable $filter
     * @return $this
     *
     * Filter left entity (morphs).
     */
    public function filterLeft(callable $filter)
    {
        $this->filterLeft = $filter;

        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getAliased()
    {
        return $this->getRightEntity()->getAlias() ?? $this->getRightEntity()->getTable();
    }
}
