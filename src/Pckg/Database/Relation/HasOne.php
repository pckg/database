<?php namespace Pckg\Database\Relation;

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
        /**
         * Get records from right entity.
         */
        $rightRecord = $this->getRightRecord(
            $this->getRightEntity(),
            $this->foreignKey,
            $record->{$this->primaryKey}
        );

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
        /**
         * Prepare relations on left records.
         */
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