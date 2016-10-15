<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Concept\Reflect;
use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Relation\Helper\MiddleEntity;

/**
 * Class HasAndBelongTo
 *
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends HasMany
{

    use MiddleEntity;

    public function mergeToQuery(Select $query)
    {
        /**
         * Join middle entity
         */
        $middleQuery = $this->getMiddleEntity()->getQuery();
        $this->getQuery()->join(
            $this->join . ' ' . $middleQuery->getTable(),
            $this->getLeftEntity()->getTable() . '.id = ' . $middleQuery->getTable() . '.' . $this->getLeftForeignKey()
        );

        /**
         * Join right entity
         */
        $rightQuery = $this->getRightEntity()->getQuery();
        $this->getQuery()->join(
            $this->join . ' ' . $rightQuery->getTable(),
            $this->getRightEntity()->getTable() . '.id = ' . $middleQuery->getTable() . '.' . $this->getRightForeignKey(
            )
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

    public function fillRecord(Record $record)
    {
        message(
            get_class($record) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity()) .
            ' Over ' . get_class($this->getMiddleEntity())
        );
        message('Record, filling ' . $this->fill . ' and pivot');

        /**
         * Get records from middle entity.
         */
        $middleCollection = $this->getMiddleCollection(
            $this->getMiddleEntity(),
            $this->leftForeignKey,
            $record->{$this->leftPrimaryKey}
        );

        /**
         * Prepare relations on left record.
         */
        $record->setRelation($this->fill, new Collection());

        /**
         * Break with execution if collection is empty.
         */
        message('Middle collection has ' . $middleCollection->count() . ' record(s)');
        if (!$middleCollection->count()) {
            return;
        }

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->rightPrimaryKey,
            $middleCollection->map($this->rightForeignKey)->unique()
        );
        message('Right collection has ' . $rightCollection->count() . ' record(s)');

        /**
         * Key collections for simpler processing.
         */
        $keyedRightCollection = $rightCollection->keyBy($this->rightPrimaryKey);

        /**
         * Set middle collection's relations.
         */
        $middleCollection->each(
            function($middleRecord) use ($record, $keyedRightCollection) {
                $rightRecord = $keyedRightCollection[$middleRecord->{$this->rightForeignKey}];
                $rightRecord->setRelation('pivot', $middleRecord);
                $record->getRelation($this->fill)->push($rightRecord);
            }
        );

        /**
         * Fill relations.
         */
        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(CollectionInterface $collection)
    {
        message(
            'Collection of ' . get_class($collection->first()) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity()) .
            ' Over ' . get_class($this->getMiddleEntity())
        );

        /**
         * Prepare relations on left records.
         */
        message('Left collection has ' . $collection->count() . ' record(s), filling ' . $this->fill);
        $collection->each(
            function(Record $record) {
                $record->setRelation($this->fill, new Collection());
            }
        );

        /**
         * Get records from middle entity.
         */
        $middleCollection = $this->getMiddleCollection(
            $this->getMiddleEntity(),
            $this->leftForeignKey,
            $collection->map($this->leftPrimaryKey)->unique()
        );

        /**
         * Break with execution if collection is empty.
         */
        message('Middle collection has ' . $middleCollection->count() . ' record(s)');
        if (!$middleCollection->count()) {
            $this->fillCollectionWithRelations($collection);

            return;
        }

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->rightPrimaryKey,
            $middleCollection->map($this->rightForeignKey)->unique()
        );
        message('Right collection has ' . $rightCollection->count() . ' record(s)');

        /**
         * Key collections for simpler processing.
         */
        $keyedLeftCollection = $collection->keyBy($this->leftPrimaryKey);
        $keyedRightCollection = $rightCollection->keyBy($this->rightPrimaryKey);

        /**
         * Set middle collection's relations.
         */
        $middleCollection->each(
            function($middleRecord) use ($keyedLeftCollection, $keyedRightCollection) {
                $rightRecord = clone $keyedRightCollection[$middleRecord->{$this->rightForeignKey}];
                $rightRecord->setRelation('pivot', $middleRecord);
                $keyedLeftCollection[$middleRecord->{$this->leftForeignKey}]->getRelation($this->fill)->push(
                    $rightRecord
                );
            }
        );

        /**
         * Fill relations.
         */
        $this->fillCollectionWithRelations($collection);
    }

}