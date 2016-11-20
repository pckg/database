<?php namespace Pckg\Database\Relation\Helper;

use Pckg\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Repository\PDO\Command\GetRecords;

trait RightEntity
{

    /**
     * @var
     */
    protected $right;

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

    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return new Collection();
        }

        $rightEntity->setRepository($this->getLeftRepository());

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

        return (new GetRecords($entity))->executeAll();
    }

    public function getRightRecord(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return null;
        }

        $rightEntity->setRepository($this->getLeftEntity()->getRepository());
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

        return (new GetRecords($entity))->executeOne();
    }

}