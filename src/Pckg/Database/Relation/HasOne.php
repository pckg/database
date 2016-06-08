<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class HasMany
 * @package Pckg\Database\Relation
 */
class HasOne extends HasMany
{

    public function fillRecord(Record $record, $debug = false)
    {
        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();
        $foreignRecord = $this->getForeignRecord($rightEntity, $foreignKey, $record->{$primaryKey});
        $record->setRelation($this->fill, null);

        if ($foreignRecord) {
            $this->fillRecordWithRelations($foreignRecord);

            $record->setRelation($this->fill, $foreignRecord);
        }
    }

    public function fillCollection(Collection $collection)
    {
        $arrPrimaryIds = [];

        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();

        foreach ($collection as $primaryRecord) {
            $arrPrimaryIds[$primaryRecord->{$primaryKey}] = $primaryRecord->{$primaryKey};
            $primaryRecord->setRelation($this->fill, null);
        }

        if ($arrPrimaryIds) {
            $foreignCollection = $this->getForeignCollection($rightEntity, $foreignKey, $arrPrimaryIds);

            foreach ($collection as $primaryRecord) {
                foreach ($foreignCollection as $foreignRecord) {
                    /*
                     * Foreign records needs to have set entity with correct table because we'll read data from
                     * repository cache in __get method.
                     */
                    $foreignRecord->setEntity($rightEntity);

                    if ($primaryRecord->{$primaryKey} == $foreignRecord->{$foreignKey}) {
                        $primaryRecord->setRelation($this->fill, $foreignRecord);
                    }
                }
            }

            $this->fillCollectionWithRelations($foreignCollection);
        }
    }

}