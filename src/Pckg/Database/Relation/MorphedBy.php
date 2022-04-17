<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Entity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class MorphedBy
 *
 * @package Pckg\Database\Relation
 */
class MorphedBy extends MorphsMany
{
    /**
     * @param Entity $middleEntity
     * @param        $foreignKey
     * @param        $primaryValue
     *
     * @return CollectionInterface
     */
    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        return $middleEntity->where($foreignKey, $primaryValue)
            ->where($this->morph, get_class($this->getLeftEntity()))
            ->all(); // call correct repository
        return (
        new GetRecords(
            $middleEntity->where($foreignKey, $primaryValue)
                         ->where($this->morph, get_class($this->getLeftEntity()))
        )
        )->executeAll();
    }
}
