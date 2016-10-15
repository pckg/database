<?php namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

class MorphedBy extends MorphsMany
{

    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        return (
        new GetRecords(
            $rightEntity->where($foreignKey, $primaryValue)
                        ->where($this->morph, get_class($this->getLeftEntity()))
        )
        )->executeAll();
    }

    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        return (
        new GetRecords(
            $middleEntity->where($foreignKey, $primaryValue)
                         ->where($this->morph, get_class($this->getLeftEntity()))
        )
        )->executeAll();
    }

}