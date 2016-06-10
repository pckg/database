<?php namespace Pckg\Database\Relation\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

trait MiddleEntity
{

    protected $middle;

    protected $leftForeignKey;

    protected $rightForeignKey;

    protected $leftPrimaryKey;

    protected $rightPrimaryKey;

    public function leftPrimaryKey($leftPrimaryKey) {
        $this->leftPrimaryKey = $leftPrimaryKey;

        return $this;
    }

    public function rightPrimaryKey($rightPrimaryKey) {
        $this->rightPrimaryKey = $rightPrimaryKey;

        return $this;
    }

    public function leftForeignKey($leftForeignKey) {
        $this->leftForeignKey = $leftForeignKey;

        return $this;
    }

    public function rightForeignKey($rightForeignKey) {
        $this->rightForeignKey = $rightForeignKey;

        return $this;
    }

    public function getLeftForeignKey() {
        return $this->leftForeignKey;
    }

    public function getRightForeignKey() {
        return $this->rightForeignKey;
    }

    /**
     * @return Entity
     * @throws \Exception
     */
    public function getMiddleEntity()
    {
        if (is_string($this->middle)) {
            $this->middle = Reflect::create($this->middle);
        }

        return $this->middle;
    }

    public function getMiddleRepository()
    {
        return $this->getMiddleEntity()->getRepository();
    }

    public function over($middle)
    {
        $this->middle = $middle;

        return $this;
    }

    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        return (new GetRecords($middleEntity->where($foreignKey, $primaryValue,
            is_array($primaryValue) ? 'IN' : '=')))->executeAll();
    }

}