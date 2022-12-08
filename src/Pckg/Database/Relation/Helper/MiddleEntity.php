<?php

namespace Pckg\Database\Relation\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class MiddleEntity
 *
 * @package Pckg\Database\Relation\Helper
 */
trait MiddleEntity
{
    protected $middle;

    /**
     * @var string
     */
    protected $leftForeignKey = 'poly_id';

    protected $rightForeignKey;

    /**
     * @var string
     */
    protected $leftPrimaryKey = 'id';

    /**
     * @var string
     */
    protected $rightPrimaryKey = 'id';

    /**
     * @return $this
     */
    public function leftPrimaryKey($leftPrimaryKey)
    {
        $this->leftPrimaryKey = $leftPrimaryKey;

        return $this;
    }

    /**
     * @return $this
     */
    public function rightPrimaryKey($rightPrimaryKey)
    {
        $this->rightPrimaryKey = $rightPrimaryKey;

        return $this;
    }

    /**
     * @return $this
     */
    public function leftForeignKey($leftForeignKey)
    {
        $this->leftForeignKey = $leftForeignKey;

        return $this;
    }

    /**
     * @return $this
     */
    public function rightForeignKey($rightForeignKey)
    {
        $this->rightForeignKey = $rightForeignKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeftForeignKey()
    {
        return $this->leftForeignKey;
    }

    /**
     * @return mixed
     */
    public function getRightForeignKey()
    {
        return $this->rightForeignKey;
    }

    /**
     * @return \Pckg\Database\Repository|\Pckg\Database\Repository\PDO
     */
    public function getMiddleRepository()
    {
        return $this->getMiddleEntity()->getRepository();
    }

    /**
     * @return Entity
     * @throws \Exception
     */
    public function getMiddleEntity()
    {
        if (is_string($this->middle)) {
            if (class_exists($this->middle)) {
                $this->middle = Reflect::create($this->middle);
            } else {
                $this->middle = (new Entity())->setTable($this->middle)->setRepository(
                    $this->getLeftEntity()->getRepository()
                );
            }
        }

        return $this->middle;
    }

    /**
     * @return $this
     */
    public function over($middle, callable $callable = null)
    {
        $this->middle = $middle;

        if ($callable) {
            $callable($this, $this->getMiddleEntity());
        }

        return $this;
    }

    /**
     * @param Entity $middleEntity
     * @param        $foreignKey
     * @param        $primaryValue
     *
     * @return \Pckg\CollectionInterface
     */
    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        return $middleEntity->where($foreignKey, $primaryValue)->all(); // to call correct repository
        return (new GetRecords($middleEntity->where($foreignKey, $primaryValue)))->executeAll();
    }
}
