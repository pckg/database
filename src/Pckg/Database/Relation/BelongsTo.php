<?php

namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\CollectionInterface;
use Pckg\Database\Record;
use Pckg\Database\Relation;

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
    public function fillRecord(Record $record)
    {
        message(
            get_class($record) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity())
        );
        message('Record, filling ' . $this->fill);

        /**
         * Get record from right entity.
         */
        $rightRecord = $this->getRightRecord(
            $this->getRightEntity(),
            $this->primaryKey,
            $record->{$this->foreignKey}
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
        message(
            get_class($collection->first()) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity())
        );

        /**
         * Prepare relations on left records.
         */
        message('Left collection has ' . $collection->count() . ' record(s), filling ' . $this->fill);
        $collection->each(
            function($record) {
                $record->setRelation($this->fill, null);
            }
        );

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->primaryKey,
            $collection->map($this->foreignKey)->unique()
        );
        message('Right collection has ' . $rightCollection->count() . ' record(s)');

        /**
         * Key collection for simpler processing.
         */
        $keyedRightCollection = $rightCollection->keyBy($this->primaryKey);

        /**
         * Set relations on left records.
         */
        $collection->each(
            function($record) use ($keyedRightCollection) {
                $record->setRelation($this->fill, $keyedRightCollection[$record->{$this->foreignKey}]);
            }
        );

        /**
         * This is needed for special case in Records.php:129
         */
        if ($this->after) {
            $collection->each($this->after);
        }

        /**
         * Fill relations.
         */
        $this->fillCollectionWithRelations($collection);
    }

    /**
     * @return string
     */
    public function getKeyCondition()
    {
        return $this->join . ' `' . $this->getRightEntity()->getTable() . '`' .
               ' ON `' . $this->getLeftEntity()->getTable() . '`.`' . $this->foreignKey . '`' .
               ' = `' . $this->getRightEntity()->getTable() . '`.`' . $this->primaryKey . '`';
    }

}