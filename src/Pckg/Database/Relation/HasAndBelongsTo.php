<?php

namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Relation\Helper\MiddleEntity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class HasAndBelongTo
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends HasMany
{

    use MiddleEntity;

    public function getLeftCollectionKey()
    {
        return Convention::nameMultiple($this->getLeftForeignKey());
    }

    public function getRightCollectionKey()
    {
        return Convention::nameMultiple($this->getRightForeignKey());
    }

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();
        $leftForeignKey = $this->getLeftForeignKey();

        $middleEntity = $this->getMiddleEntity();
        $rightEntity = $this->getRightEntity();

        $leftCollectionKey = $this->getLeftCollectionKey();
        $rightCollectionKey = $this->getRightCollectionKey();

        $rightRecordKey = Convention::nameOne($leftForeignKey);
        $leftRecordKey = Convention::nameOne($rightForeignKey);

        // get records from middle (mtm) entity
        $middleCollection = $this->getMiddleCollection($middleEntity, $leftForeignKey, $record->id);

        // get right record ids and preset middle record with null values
        $arrRightIds = [];
        foreach ($middleCollection as $middleRecord) {
            $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
            $middleRecord->setRelation($rightRecordKey, $record);
            $middleRecord->setRelation($leftRecordKey, null);
        }

        // prepare record for mtm relation and right relation
        $record->setRelation($this->fill, $middleCollection);
        $record->setRelation($rightCollectionKey, new Collection());

        if ($arrRightIds) {
            // get all right records
            $rightCollection = $this->getRightCollection($rightEntity, 'id', $arrRightIds);

            // set relation
            $record->setRelation($rightCollectionKey, $rightCollection);

            // we also have to fill it with relations
            $this->fillCollectionWithRelations($record->getRelation($rightCollectionKey));

            // we need to link middle record with left and right records
            foreach ($rightCollection as $rightRecord) {
                foreach ($middleCollection as $middleRecord) {
                    $middleRecord->setRelation($leftRecordKey, $rightRecord);
                    $rightRecord->setRelation($leftCollectionKey, $middleRecord);
                }
            }
        }

        // also fill current relation's relations
        $this->fillRecordWithRelations($record);
    }

    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        return (new GetRecords($rightEntity->where($foreignKey, $primaryValue,
            is_array($primaryValue) ? 'IN' : '=')))->executeAll();
    }

    public function fillCollection(Collection $collection)
    {
        $arrLeftIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        $leftForeignKey = $this->getLeftForeignKey();

        $middleEntity = $this->getMiddleEntity();
        $rightEntity = $this->getRightEntity();

        $rightCollectionKey = Convention::nameMultiple($rightForeignKey);
        $rightRecordKey = Convention::nameMultiple($leftForeignKey);
        $leftCollectionKey = Convention::nameMultiple($leftForeignKey);
        $leftRecordKey = Convention::nameMultiple($rightForeignKey);
        foreach ($collection as $record) {
            $arrLeftIds[$record->id] = $record->id;
            $record->{$rightCollectionKey} = new Collection();
        }

        if ($arrLeftIds) {
            // middle collection is filled with it's entity relations
            $middleCollection = $this->getMiddleCollection($middleEntity, $leftForeignKey, $arrLeftIds);
            $arrRightIds = [];
            foreach ($middleCollection as $middleRecord) {
                $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
                $middleRecord->{$rightRecordKey} = null;
                $middleRecord->{$leftRecordKey} = null;
            }

            if ($arrRightIds) {
                // foreign collection is filled with it's entity relations
                $rightCollection = $this->getRightCollection($rightEntity, 'id', $arrRightIds);
                foreach ($rightCollection as $rightRecord) {
                    $rightRecord->{$leftCollectionKey} = new Collection();
                }

                // we have to fill it with current relations
                $this->fillCollectionWithRelations($rightCollection);

                foreach ($middleCollection as $middleRecord) {
                    foreach ($collection as $leftRecord) {
                        if ($leftRecord->id == $middleRecord->{$leftForeignKey}) {
                            foreach ($rightCollection as $rightRecord) {
                                if ($rightRecord->id == $middleRecord->{$rightForeignKey}) {
                                    $leftRecord->{$rightCollectionKey}->push($rightRecord);
                                    $rightRecord->{$leftCollectionKey}->push($rightRecord);
                                    $middleRecord->{$leftRecordKey} = $rightRecord;
                                    $middleRecord->{$rightRecordKey} = $leftRecord;
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->fillCollectionWithRelations($collection);
    }

}