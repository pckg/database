<?php

namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class BelongsTo
 * @package Pckg\Database\Relation
 */
class BelongsTo extends Relation
{

    /**
     * @param Record $record
     *
     * @return mixed
     *
     * Example: layout belongs to variable.
     */
    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();

        $rightEntity = $this->getRightEntity();
        if ($record->{$rightForeignKey}) {
            $record->setRelation($this->fill,
                (new GetRecords($rightEntity->where('id', $record->{$rightForeignKey})))->executeOne());
        } else {
            $record->setRelation($this->fill, null);
        }

        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(Collection $collection)
    {
        $arrIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                $arrIds[$record->{$rightForeignKey}] = $record->{$rightForeignKey};
                $record->setRelation($this->fill, null);
            }
        }

        $rightEntity = clone $this->getRightEntity();
        $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrIds);
        foreach ($collection as $record) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->id == $record->{$rightForeignKey}) {
                    $record->setRelation($this->fill, $foreignRecord);
                    break;
                }
            }
        }
    }

}