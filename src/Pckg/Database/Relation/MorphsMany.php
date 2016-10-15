<?php namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

class MorphsMany extends HasAndBelongsTo
{

    protected $leftForeignKey = 'poly_id';

    protected $morph = 'morph_id';

    public function poly($poly)
    {
        $this->leftForeignKey = $poly;

        return $this;
    }

    public function morph($morph)
    {
        $this->morph = $morph;

        return $this;
    }

    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return new Collection();
        }
        
        return (
        new GetRecords(
            $rightEntity->where($foreignKey, $primaryValue)
                        ->where($this->morph, get_class($this->getLeftEntity()))
        )
        )->executeAll();
    }

    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        if (!$primaryValue) {
            return new Collection();
        }

        return (
        new GetRecords(
            $middleEntity->where($foreignKey, $primaryValue)
                         ->where($this->morph, get_class($this->getLeftEntity()))
        )
        )->executeAll();
    }

}