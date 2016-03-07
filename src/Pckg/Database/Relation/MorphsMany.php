<?php namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

class MorphsMany extends HasAndBelongsTo
{

    protected $poly = 'poly_id';

    protected $morph = 'morph_id';

    public function poly($poly)
    {
        $this->poly = $poly;

        return $this;
    }

    public function morph($morph)
    {
        $this->morph = $morph;

        return $this;
    }

    public function getLeftForeignKey()
    {
        return $this->poly;
    }

    public function getLeftCollectionKey()
    {
        return 'poly';
    }

    public function getForeignCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        return (new GetRecords($rightEntity->where($foreignKey, $primaryValue, is_array($primaryValue) ? 'IN' : '=')
            ->where($this->morph, get_class($this->getLeftEntity()))))->executeAll();
    }

    public function getMiddleCollection(Entity $middleEntity, $foreignKey, $primaryValue)
    {
        return (new GetRecords($middleEntity->where($foreignKey, $primaryValue, is_array($primaryValue) ? 'IN' : '=')
            ->where($this->morph, get_class($this->getLeftEntity()))))->executeAll();
    }

}