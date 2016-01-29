<?php

namespace Pckg\Database;

use Pckg\Database\Query\Helper\With;
use Pckg\Concept\Reflect;

/**
 * Class Relation
 * @package Pckg\Database
 */
abstract class Relation
{

    use With;

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
    protected $right;

    /**
     * @var
     */
    protected $on;

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

    /**
     * @return Entity
     * @throws \Exception
     */
    public function getRightEntity()
    {
        if (is_string($this->right)) {
            $this->right = Reflect::create($this->right);
        }

        return $this->right;
    }

    /**
     * @return Repository
     * @throws \Exception
     */
    public function getRightRepository()
    {
        return $this->getRightEntity()->getRepository();
    }

    public function onRecord(Record $record)
    {
        $this->record = $record;

        return $this;
    }

    abstract function fillRecord(Record $record);

    abstract function fillCollection(Collection $collection);

}