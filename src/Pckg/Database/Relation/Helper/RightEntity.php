<?php namespace Pckg\Database\Relation\Helper;

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

    public function getForeignCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        $entity = $rightEntity->where($foreignKey, $primaryValue, is_array($primaryValue) ? 'IN' : '=');

        foreach ($this->getQuery()->getWhere()->getChildren() as $condition) {
            $entity->where(function (Parenthesis $parenthesis) use ($condition) {
                $parenthesis->push($condition);
            });

            foreach ($this->getQuery()->getBinds('where') as $bind) {
                $entity->getQuery()->bind($bind, 'where');
            }
        }

        return (new GetRecords($entity))->executeAll();
    }

    public function getForeignRecord(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        $entity = $rightEntity->where($foreignKey, $primaryValue, is_array($primaryValue) ? 'IN' : '=');

        foreach ($this->getQuery()->getWhere()->getChildren() as $condition) {
            $entity->where(function (Parenthesis $parenthesis) use ($condition) {
                $parenthesis->push($condition);
            });

            foreach ($this->getQuery()->getBinds('where') as $bind) {
                $entity->getQuery()->bind($bind, 'where');
            }
        }

        /*foreach ($this->select as $key => $select) {
            if (is_numeric($key)) {
                $entity->getQuery()->addSelect([$select]);

            } else {
                $entity->getQuery()->addSelect([$key => $select]);

            }
        }*/

        return (new GetRecords($entity))->executeOne();
    }

}