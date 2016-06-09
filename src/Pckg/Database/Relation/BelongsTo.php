<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class BelongsTo
 *
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
    public function fillRecord(Record $record) {
        $foreignKey = $this->getForeignKey();
        $rightEntity = $this->getRightEntity();

        if ($record->{$foreignKey}) {
            $record->setRelation(
                $this->fill,
                (new GetRecords($rightEntity->where('id', $record->{$foreignKey})))->executeOne()
            );
        } else {
            $record->setRelation($this->fill, null);
        }

        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(Collection $collection) {
        if (!$collection->count()) {
            return $collection;
        }

        $arrPrimaryIds = [];
        $rightEntity = $this->getRightEntity();

        $foreignKey = $this->getForeignKey();
        $primaryKey = $this->getPrimaryKey();

        foreach ($collection as $record) {
            if ($record->{$foreignKey}) {
                $arrPrimaryIds[$record->{$foreignKey}] = $record->{$foreignKey};
                $record->setRelation($this->fill, null);
            }
        }

        $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrPrimaryIds);
        foreach ($collection as $primaryRecord) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->{$primaryKey} == $primaryRecord->{$foreignKey}) {
                    $primaryRecord->setRelation($this->fill, $foreignRecord);
                    break;
                }
            }
        }

        $this->fillCollectionWithRelations($foreignCollection);

        if ($this->after) {
            $collection->each($this->after);
        }
    }

}