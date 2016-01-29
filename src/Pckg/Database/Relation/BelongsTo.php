<?php

namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class BelongsTo
 * @package Pckg\Database\Relation
 */
class BelongsTo extends Relation
{

    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function fillRecord(Record $record)
    {
        //d('BelongsTo::fillRecord() ' . get_class($record));
        $rightForeignKey = $this->getRightForeignKey();

        $rightEntity = clone $this->getRightEntity();
        if ($record->{$rightForeignKey}) {
            $record->{substr($rightForeignKey, 0, -3)} = (new GetRecords($rightEntity->where('id', $record->{$rightForeignKey})))->executeOne();
        } else {
            $record->{substr($rightForeignKey, 0, -3)} = null;
        }

        $this->fillRecordWithRelations($record);
        //(new GetRecords($this->getRightEntity()))->fillRecord($record);
    }

    public function fillCollection(Collection $collection)
    {
        //d('BelongsTo::fillCollection() ' . get_class($collection[0]) . ' ' . get_class($this->getRightEntity()));
        $arrIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        $rightRecordKey = Convention::nameOne($rightForeignKey);
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                $arrIds[$record->{$rightForeignKey}] = $record->{$rightForeignKey};
                $record->{$rightRecordKey} = null;
            }
        }

        $rightEntity = clone $this->getRightEntity();
        $foreignCollection = (new GetRecords($rightEntity->where($rightEntity->getPrimaryKey(), $arrIds, 'IN')))->executeAll();
        foreach ($collection as $record) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->id == $record->{$rightForeignKey}) {
                    $record->{$rightRecordKey} = $foreignRecord;
                    break;
                }
            }
        }

        //$this->fillCollectionWithRelations($collection);
        //(new GetRecords($this->getRightEntity()))->fillCollection($collection);
    }

    public function getRelationValue($key)
    {
        $value = $this->record->{$key . '_id'};

        return $value;
    }

}