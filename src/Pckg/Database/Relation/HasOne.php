<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class HasMany
 *
 * @package Pckg\Database\Relation
 */
class HasOne extends HasMany
{

    public function fillRecord(Record $record)
    {
        message(
            get_class($record) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity())
        );

        /**
         * Get records from right entity.
         */
        $rightRecord = $this->getRightRecord(
            $this->getRightEntity(),
            $this->foreignKey,
            $record->{$this->primaryKey}
        );
        message('Right collection has ' . ($rightRecord ? 1 : 0) . ' record(s)');

        /**
         * Set relation.
         */
        $record->setRelation($this->fill, $rightRecord);

        /**
         * Fill relations.
         */
        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(CollectionInterface $collection)
    {
        message(
            'Collection of ' . get_class($collection->first()) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity())
        );

        /**
         * Prepare relations on left records.
         */
        message('Left collection has ' . $collection->count() . ' record(s)');
        $collection->each(
            function(Record $record) {
                $record->setRelation($this->fill, null);
            }
        );

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->foreignKey,
            $collection->map($this->primaryKey)->unique()
        );
        message('Right collection has ' . $rightCollection->count() . ' record(s)');

        /**
         * Key collection for simpler processing.
         */
        $keyedCollection = $collection->keyBy($this->primaryKey);

        /**
         * Set relations on left records.
         */
        $rightCollection->each(
            function($rightRecord) use ($keyedCollection) {
                $keyedCollection[$rightRecord->{$this->foreignKey}]->setRelation($this->fill, $rightRecord);
            }
        );

        /**
         * Fill relations.
         */
        $this->fillCollectionWithRelations($collection);
    }

}