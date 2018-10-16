<?php namespace Pckg\Database\Relation\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class RightEntity
 *
 * @package Pckg\Database\Relation\Helper
 */
trait RightEntity
{

    /**
     * @var
     */
    protected $right;

    /**
     * @var bool
     */
    protected $inheritRightRepository = true;

    /**
     * @return Repository
     * @throws \Exception
     */
    public function getRightRepository()
    {
        return $this->getRightEntity()->getRepository();
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
     * @return string
     */
    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    /**
     * @param Entity $rightEntity
     * @param        $foreignKey
     * @param        $primaryValue
     *
     * @return \Pckg\CollectionInterface|Collection
     */
    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return new Collection();
        }

        if ($this->inheritRightRepository) {
            $rightEntity->setRepository($this->getLeftRepository());
        }

        $entity = $rightEntity->where($foreignKey, $primaryValue);

        foreach ($this->getQuery()->getWhere()->getChildren() as $condition) {
            $entity->where(
                function(Parenthesis $parenthesis) use ($condition) {
                    $parenthesis->push($condition);
                }
            );
        }

        foreach ($this->getQuery()->getBinds('where') as $bind) {
            $entity->getQuery()->bind($bind, 'where');
        }

        return (new GetRecords($rightEntity))->executeAll();
    }

    /**
     * @param Entity $rightEntity
     * @param        $foreignKey
     * @param        $primaryValue
     *
     * @return null|\Pckg\Database\Record
     */
    public function getRightRecord(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return null;
        }

        if ($this->inheritRightRepository) {
            $rightEntity->setRepository($this->getLeftEntity()->getRepository());
        }
        $entity = $rightEntity->where($foreignKey, $primaryValue);

        /**
         * Add conditions applied on query.
         */
        foreach ($this->getQuery()->getWhere()->getChildren() as $condition) {
            $entity->where(
                function(Parenthesis $parenthesis) use ($condition) {
                    $parenthesis->push($condition);
                }
            );
        }

        foreach ($this->getQuery()->getBinds('where') as $bind) {
            $entity->getQuery()->bind($bind, 'where');
        }
        
        return (new GetRecords($rightEntity))->executeOne();
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function inheritRightRepository($bool = true)
    {
        $this->inheritRightRepository = $bool;

        return $this;
    }

}