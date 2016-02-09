<?php

namespace Pckg\Database;

use Pckg\Collection;
use Pckg\Database\Query\Helper\With;
use Pckg\Concept\Reflect;
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

    /**
     * @param $left
     * @param $right
     */
    public function __construct($left, $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        $this->callWith($method, $args, $this->right);

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

    public function getCondition()
    {
        return $this->getKeyCondition();
    }

    public function getKeyCondition() {
        return ' INNER JOIN `' . $this->getRightEntity()->getTable() . '`' .
        ' ON `' . $this->getLeftEntity()->getTable() . '`.`' . $this->getPrimaryKey() . '`' .
        ' = `' . $this->getRightEntity()->getTable() . '`.`' . $this->getForeignKey() . '`';
    }

    public function getAdditionalCondition(){
        return $this->onAdditional
            ? ' AND ' . $this->onAdditional
            : '';
    }

    abstract function fillRecord(Record $record);

    abstract function fillCollection(Collection $collection);

}