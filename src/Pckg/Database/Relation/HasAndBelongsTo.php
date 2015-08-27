<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class HasAndBelongTo
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends Relation
{

    protected $middleTable;

    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function getLeftForeignKey()
    {
        $class = explode('\\', get_class($this->left));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function middleTable($middleTable)
    {
        $this->middleTable = $middleTable;

        return $this;
    }

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();
        $leftForeignKey = $this->getLeftForeignKey();
        $middleTable = $this->middleTable ?: Convention::nameMultiple(substr($rightForeignKey, 0, -3)) . '_' . Convention::nameMultiple(substr($leftForeignKey, 0, -3));

        $select = (new Select())->fields([$rightForeignKey])
            ->table($middleTable)
            ->where($leftForeignKey, $record->id);

        $record->{Convention::nameMultiple(substr($rightForeignKey, 0, -3))} = (new GetRecords($this->getRightEntity()->where('id', $select, 'IN'), $this->getRightEntity()->getRepository()))->executeAll();

        $this->fillWithRecord($record);
    }

    public function fillCollection(Collection $collection)
    {
        $arrIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                $arrIds[$record->{$rightForeignKey}] = $record->{$rightForeignKey};
                $record->{substr($rightForeignKey, 0, -3)} = new Collection();
            }
        }

        $foreignCollection = (new GetRecords($this->getRightEntity()->where('id', $arrIds, 'IN'), $this->getRightEntity()->getRepository()))->executeAll();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                foreach ($foreignCollection as $foreignRecord) {
                    if ($foreignRecord->id == $record->{$rightForeignKey}) {
                        $record->{substr($rightForeignKey, 0, -3)}->push($foreignRecord);
                    }
                }
            }
        }

        $this->fillWithCollection($collection);
    }

}