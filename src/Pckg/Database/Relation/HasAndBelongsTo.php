<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;
use Pckg\Framework\Helper\Reflect;

/**
 * Class HasAndBelongTo
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends HasMany
{

    protected $middle;

    /**
     * @return Entity
     * @throws \Exception
     */
    public function getMiddleEntity()
    {
        if (is_string($this->middle)) {
            $this->middle = Reflect::create($this->middle);
        }

        return $this->middle;
    }

    public function getMiddleRepository()
    {
        return $this->getMiddleEntity()->getRepository();
    }

    public function over($middle)
    {
        $this->middle = $middle;

        return $this;
    }

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();
        $leftForeignKey = $this->getLeftForeignKey();

        $middleEntity = $this->getMiddleEntity();
        $middleEntity->resetQuery();
        $rightEntity = $this->getRightEntity();
        $rightEntity->resetQuery();

        $leftCollectionKey = Convention::nameMultiple($leftForeignKey);
        $middleCollectionKey = lcfirst(Convention::toCamel($middleEntity->getTable()));
        $rightCollectionKey = Convention::nameMultiple($rightForeignKey);

        $rightRecordKey = Convention::nameOne($leftForeignKey);
        $leftRecordKey = Convention::nameOne($rightForeignKey);

        // get mtm records
        $middleCollection = (new GetRecords($middleEntity->where($leftForeignKey, $record->id)))->executeAll();

        // get right record ids and preset middle record with null values
        $arrRightIds = [];
        foreach ($middleCollection as $middleRecord) {
            $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
            $middleRecord->{$rightRecordKey} = null;
            $middleRecord->{$leftRecordKey} = null;
        }

        // prepare record for mtm relation and right relation
        $record->{$middleCollectionKey} = $middleCollection;
        $record->{$rightCollectionKey} = new Collection();

        if ($arrRightIds) {
            // get all right records
            $rightCollection = (new GetRecords($rightEntity->where('id', $arrRightIds, 'IN')))->executeAll();

            // prepare right collection records for left relation
            foreach ($rightCollection as $rightRecord) {
                $rightRecord->{$leftCollectionKey} = new Collection();
            }

            $record->{$rightCollectionKey} = $rightCollection;

            // we also have to fill it with relations
            $this->fillCollectionWithRelations($record->{$rightCollectionKey});

            foreach ($middleCollection as $middleRecord) {
                $middleRecord->{$rightRecordKey} = $record;

                foreach ($rightCollection as $rightRecord) {
                    $middleRecord->{$leftRecordKey} = $rightRecord;
                    $rightRecord->{$leftCollectionKey}->push($rightRecord);
                }
            }
        }

        // also fill current relation's relations
        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(Collection $collection)
    {
        $arrLeftIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        $leftForeignKey = $this->getLeftForeignKey();

        $middleEntity = $this->getMiddleEntity();
        $middleEntity->resetQuery();
        $rightEntity = $this->getRightEntity();
        $rightEntity->resetQuery();

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
            $middleCollection = (new GetRecords($middleEntity->where($leftForeignKey, $arrLeftIds, 'IN')))->executeAll();
            $arrRightIds = [];
            foreach ($middleCollection as $middleRecord) {
                $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
                $middleRecord->{$rightRecordKey} = null;
                $middleRecord->{$leftRecordKey} = null;
            }

            if ($arrRightIds) {
                // foreign collection is filled with it's entity relations
                $rightCollection = (new GetRecords($rightEntity->where('id', $arrRightIds, 'IN')))->executeAll();
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

        // $this->fillCollectionWithRelations($collection);
    }

}