<?php

namespace Pckg\Database;

use Pckg\Database\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Query\Helper\With;
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

    protected $after;

    public function addSelect($fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            $this->select[] = $field;
        }

        return $this;
    }

    public function addCondition($conditions = [])
    {
        if (!is_array($conditions)) {
            $conditions = [$conditions];
        }

        foreach ($conditions as $condition) {
            $this->condition[] = $condition;
        }

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
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
        $this->callWith($method, $args, $this->getRightEntity());

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

    abstract function fillRecord(Record $record);

    abstract function fillCollection(Collection $collection);

}