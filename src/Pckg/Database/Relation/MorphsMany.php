<?php namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class MorphsMany
 *
 * @package Pckg\Database\Relation
 */
class MorphsMany extends HasAndBelongsTo
{

    /**
     * @var string
     */
    protected $leftForeignKey = 'poly_id';

    /**
     * @var string
     */
    protected $morph = 'morph_id';

    /**
     * @param $poly
     *
     * @return $this
     */
    public function poly($poly)
    {
        $this->leftForeignKey = $poly;

        return $this;
    }

    /**
     * @param $morph
     *
     * @return $this
     */
    public function morph($morph)
    {
        $this->morph = $morph;

        return $this;
    }

    /**
     * @param Entity $middleEntity
     * @param        $foreignKey
     * @param        $primaryValue
     *
     * @return Collection|\Pckg\CollectionInterface
     */
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

    /**
     * @return string
     */
    public function getMiddleKeyCondition()
    {
        $middleQuery = $this->getMiddleEntity()->getQuery();

        return parent::getMiddleKeyCondition() . ' AND `' . $middleQuery->getTable() . '`.`' . $this->morph . '` = ?';
    }

    /**
     * @return array
     */
    public function getMiddleKeyBinds()
    {
        return [get_class($this->getLeftEntity())];
    }

}