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

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();

        $rightEntity = clone $this->getRightEntity();
        if ($record->{$rightForeignKey}) {
            $record->{substr($rightForeignKey, 0, -3)} = (new GetRecords($rightEntity->where('id', $record->{$rightForeignKey})))->executeOne();
        } else {
            $record->{substr($rightForeignKey, 0, -3)} = null;
        }

        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(Collection $collection)
    {
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
        $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrIds);
        foreach ($collection as $record) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->id == $record->{$rightForeignKey}) {
                    $record->{$rightRecordKey} = $foreignRecord;
                    break;
                }
            }
        }
    }

    public function getRelationValue($key)
    {
        $value = $this->record->{$key . '_id'};

        return $value;
    }

}