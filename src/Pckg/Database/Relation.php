<?php namespace Pckg\Database;

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

    use With, RightEntity, QueryBuilder;

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

    protected $primaryKey = 'id';

    protected $primaryCollectionKey;

    protected $foreignKey;

    protected $select = [];

    /**
     * @var Select
     */
    protected $query;

    protected $after;

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
             */
            message(get_class($this) . '->__call(' . $method . ') on query ' . get_class($this->getQuery()));
            Reflect::method($this->getQuery(), $method, $args);

        } elseif (method_exists($this->getRightEntity(), $method)) {
            /**
             * Then right entity.
             */
            message(
                get_class($this) . '->__call(' . $method . ') on right entity ' . get_class($this->getRightEntity())
            );
            Reflect::method($this->getRightEntity(), $method, $args);

        } else {
            message(
                get_class($this) . '->__call(' . $method . ') with right entity ' . get_class($this->getRightEntity())
            );
            $this->callWith($method, $args, $this->getRightEntity());

        }

        return $this;
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
    public function innerJoin()
    {
        $this->join = static::INNER_JOIN;

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
     * @return string
     */
    public function getKeyCondition()
    {
        $rightEntity = $this->getRightEntity();
        $rightAlias = $rightEntity->getAlias() ?? $rightEntity->getTable();

        return $this->join . ' `' . $rightEntity->getTable() . '` AS `' . $rightAlias . '`' .
               ($this->primaryKey && $this->foreignKey ? ' ON `' . $this->getLeftEntity()->getTable(
                   ) . '`.`' . $this->primaryKey . '`' .
                                                         ' = `' . $rightAlias . '`.`' . $this->foreignKey . '`' : '');
    }

    public function getAdditionalCondition()
    {
        return $this->onAdditional
            ? ' AND ' . $this->onAdditional
            : '';
    }

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
            /**
             * Is this ok to be commented?
             */
            //$query->addSelect([$key => $select]);
        }

        return $this;
    }

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

}