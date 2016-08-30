<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Concept\Reflect;
use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Relation\Helper\MiddleEntity;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class HasAndBelongTo
 *
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends HasMany
{

    use MiddleEntity;

    public function getLeftCollectionKey()
    {
        return Convention::nameMultiple($this->leftForeignKey);
    }

    public function getRightCollectionKey()
    {
        return Convention::nameMultiple($this->rightForeignKey);
    }

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->rightForeignKey;
        $leftForeignKey = $this->leftForeignKey;

        $middleEntity = $this->getMiddleEntity();
        $rightEntity = $this->getRightEntity();

        $leftCollectionKey = $this->getLeftCollectionKey();
        $rightCollectionKey = $this->fill;

        // get records from middle (mtm) entity
        message('getting middle collection ' . get_class($middleEntity) . ' ' . $leftForeignKey . ' = ' . $record->id);
        $middleCollection = $this->getMiddleCollection($middleEntity, $leftForeignKey, $record->id);

        // get right record ids and preset middle record with null values
        $arrRightIds = [];
        foreach ($middleCollection as $middleRecord) {
            $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
            $middleRecord->setRelation($rightForeignKey, $record);
            $middleRecord->setRelation($leftForeignKey, null);
        }

        // prepare record for mtm relation and right relation
        $record->setRelation($this->fill, $middleCollection);
        $record->setRelation($rightCollectionKey, new Collection());
        message($this->fill . ' - ' . $rightCollectionKey);

        if ($arrRightIds) {
            // get all right records
            message('getting right collection ' . get_class($rightEntity) . ' id ' . implode(',', $arrRightIds));
            $rightCollection = $this->getRightCollection($rightEntity, 'id', $arrRightIds);

            // set relation
            message('setting record relation ' . $rightCollectionKey . ' ' . $rightCollection->count());
            $record->setRelation($rightCollectionKey, $rightCollection);

            // we also have to fill it with relations
            $this->fillCollectionWithRelations($record->getRelation($rightCollectionKey));

            // we need to link middle record with left and right records
            foreach ($rightCollection as $rightRecord) {
                foreach ($middleCollection as $middleRecord) {
                    $middleRecord->setRelation($leftForeignKey, $rightRecord);
                    $rightRecord->setRelation($leftCollectionKey, $middleRecord);
                    $rightRecord->setRelation('pivot', $middleRecord);
                }
            }
        }

        // also fill current relation's relations
        $this->fillRecordWithRelations($record);
    }

    public function getRightCollection(Entity $rightEntity, $foreignKey, $primaryValue)
    {
        return (
        new GetRecords(
            $rightEntity->where(
                $foreignKey,
                $primaryValue,
                is_array($primaryValue) ? 'IN' : '='
            )
        )
        )->executeAll();
    }

    public function fillCollection(CollectionInterface $collection)
    {
        $arrLeftIds = [];

        $rightForeignKey = $this->rightForeignKey;
        $leftForeignKey = $this->leftForeignKey;

        $middleEntity = $this->getMiddleEntity();
        $rightEntity = $this->getRightEntity();

        $rightCollectionKey = $this->fill;
        foreach ($collection as $record) {
            $arrLeftIds[$record->id] = $record->id;
            $record->setRelation($rightCollectionKey, new Collection());
        }

        if ($arrLeftIds) {
            // middle collection is filled with it's entity relations
            $middleCollection = $this->getMiddleCollection($middleEntity, $leftForeignKey, $arrLeftIds);
            $arrRightIds = [];
            foreach ($middleCollection as $middleRecord) {
                $arrRightIds[$middleRecord->{$rightForeignKey}] = $middleRecord->{$rightForeignKey};
            }

            if ($arrRightIds) {
                // foreign collection is filled with it's entity relations
                $rightCollection = $this->getRightCollection($rightEntity, 'id', $arrRightIds);

                // we have to fill it with current relations
                $this->fillCollectionWithRelations($rightCollection);

                // prepare collections for faster processing
                $keyedLeftCollection = $collection->keyBy('id');
                $keyedRightCollection = $rightCollection->keyBy('id');

                foreach ($middleCollection as $middleRecord) {
                    $keyedLeftCollection[$middleRecord->{$leftForeignKey}]
                        ->getRelation($rightCollectionKey)
                        ->push($keyedRightCollection[$middleRecord->{$rightForeignKey}]);
                    $keyedRightCollection[$middleRecord->{$rightForeignKey}]
                        ->setRelation('pivot', $middleRecord);
                }
            }
        }

        $this->fillCollectionWithRelations($collection);
    }

    public function mergeToQuery(Select $query)
    {
        /**
         * Join middle entity
         */
        $middleQuery = $this->getMiddleEntity()->getQuery();
        $this->getQuery()->join($this->join . ' ' . $middleQuery->getTable(),
                                $this->getLeftEntity()->getTable() . '.id = ' . $middleQuery->getTable(
                                ) . '.' . $this->getLeftForeignKey()
        );

        /**
         * Join right entity
         */
        $rightQuery = $this->getRightEntity()->getQuery();
        $this->getQuery()->join($this->join . ' ' . $rightQuery->getTable(),
                                $this->getRightEntity()->getTable() . '.id = ' . $middleQuery->getTable(
                                ) . '.' . $this->getRightForeignKey()
        );

        /**
         * Add select fields
         */
        foreach ($this->getSelect() as $key => $val) {
            if (is_numeric($key)) {
                $this->getQuery()->prependSelect([$val]);
            } else {
                $this->getQuery()->addSelect([$key => $val]);
            }
        }
        foreach ($this->getMiddleEntity()->getQuery()->getSelect() as $key => $val) {
            if (is_numeric($key)) {
                $this->getQuery()->prependSelect([$val]);
            } else {
                $this->getQuery()->addSelect([$key => $val]);
            }
        }
        foreach ($this->getRightEntity()->getQuery()->getSelect() as $key => $val) {
            if (is_numeric($key)) {
                $this->getQuery()->prependSelect([$val]);
            } else {
                $this->getQuery()->addSelect([$key => $val]);
            }
        }
    }

}