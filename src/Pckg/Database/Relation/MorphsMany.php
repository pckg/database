<?php namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Entity;
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

    public function getMiddleKeyCondition()
    {
        $middleQuery = $this->getMiddleEntity()->getQuery();

        return parent::getMiddleKeyCondition() . ' AND `' . $middleQuery->getTable() . '`.`' . $this->morph . '` = ?';
    }

    public function getMiddleKeyBinds()
    {
        return [get_class($this->getLeftEntity())];
    }

}