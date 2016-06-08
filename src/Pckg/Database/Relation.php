<?php

namespace Pckg\Database;

use Pckg\Database\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Query\Select;
use Pckg\Database\Relation\Helper\RightEntity;

/**
 * Class Relation
 * @package Pckg\Database
 */
abstract class Relation
{

    use With, RightEntity;

    /**
     *
     */
    const LEFT_JOIN = 'LEFT JOIN';
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

    protected $onAdditional;

    protected $record;

    protected $fill;

    protected $primaryKey;

    protected $primaryCollectionKey;

    protected $foreignKey;

    protected $select = [];

    protected $condition = [];

    /**
     * @var Select
     */
    protected $query;

    protected $after;

    public function addSelect($fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $key => $field) {
            if (is_numeric($key)) {
                $this->select[] = $field;

            } else {
                $this->select[$key] = $field;

            }
        }

        return $this;
    }

    public function where($key, $value = true, $operator = '=') {
        $this->getQuery();

        $this->getRightEntity()->where($key, $value, $operator);

        return $this;
    }

    public function getQuery()
    {
        if (!$this->query) {
            $this->query = new Select();
        }

        return $this->query;
    }

    public function primaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    public function foreignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey
            ? $this->primaryKey
            : $this->getLeftForeignKey();
    }

    public function getForeignKey()
    {
        return $this->foreignKey
            ? $this->foreignKey
            : $this->getRightForeignKey();
    }

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

    protected function getCalee($depth = 3)
    {
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
        $rightEntity = $this->getRightEntity();

        if (method_exists($rightEntity, $method)) {
            Reflect::method($rightEntity, $method, $args);
            
        } else {
            $this->callWith($method, $args, $this->getRightEntity());

        }

        return $this;
    }

    /**
     * @param $join
     */
    public function join($join)
    {
        $this->join = $join;
    }

    /**
     * @return $this
     */
    public function leftJoin()
    {
        $this->join = static::LEFT_JOIN;

        return $this;
    }

    public function fill($fill)
    {
        $this->fill = $fill;

        return $this;
    }

    public function after($after)
    {
        $this->after = $after;

        return $this;
    }

    public function getFill()
    {
        return $this->fill;
    }

    /**
     * @param $on
     *
     * @return $this
     */
    public function on($on)
    {
        $this->on = $on;

        return $this;
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
     * @return Repository
     * @throws \Exception
     */
    public function getLeftRepository()
    {
        return $this->getLeftEntity()->getRepository();
    }

    public function onRecord(Record $record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @T00D00 - join type needs to be dynamic!
     *
     * @return string
     */
    public function getKeyCondition()
    {
        return 'LEFT JOIN `' . $this->getRightEntity()->getTable() . '`' .
        ' ON `' . $this->getLeftEntity()->getTable() . '`.`' . $this->getPrimaryKey() . '`' .
        ' = `' . $this->getRightEntity()->getTable() . '`.`' . $this->getForeignKey() . '`';
    }

    public function getAdditionalCondition()
    {
        return $this->onAdditional
            ? ' AND ' . $this->onAdditional
            : '';
    }

    public function mergeToQuery(Select $query)
    {
        $condition = '';
        if ($this->getQuery()->getWhere()->hasChildren()) {
            $condition = ' AND ' . $this->getQuery()->getWhere()->build();

            foreach ($this->query->getBinds('where') as $bind) {
                $query->bind($bind, 'where');
            }
        }

        $query->join($this->getKeyCondition() . $condition);

        foreach ($this->select as $select) {
            $query->prependSelect($select);
        }

        return $this;
    }

    abstract function fillRecord(Record $record);

    abstract function fillCollection(Collection $collection);

}