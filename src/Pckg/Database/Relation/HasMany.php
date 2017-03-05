<?php namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\CollectionInterface;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class HasMany
 *
 * @package Pckg\Database\Relation
 */
class HasMany extends Relation
{

    /**
     * Attaches $sth to HasMany relation.
     * For example, we attach user to status like: $status->attach($user);
     *
     * @T00D00 - detach, ...
     *
     * @return $this
     */
    public function attach($sth)
    {
        return $this;
    }

    public function fillRecord(Record $record)
    {
        message(
            get_class($record) . ' (' . get_class($this->getLeftEntity()) . ')' .
            ' ' . get_class($this) . ' ' . get_class($this->getRightEntity())
        );
        message('Record, filling ' . $this->fill);

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->foreignKey,
            $record->{$this->primaryKey}
        );
        message('Right collection has ' . $rightCollection->count() . ' record(s)');

        /**
         * Set relation.
         */
        $record->setRelation($this->fill, $rightCollection);

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
        message('Left collection has ' . $collection->count() . ' record(s), filling ' . $this->fill);
        $collection->each(
            function(Record $record) {
                $record->setRelation($this->fill, new Collection());
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
                if ($keyedCollection->hasKey($rightRecord->{$this->foreignKey})) {
                    $keyedCollection[$rightRecord->{$this->foreignKey}]->getRelation($this->fill)->push($rightRecord);
                }
            }
        );

        /**
         * Fill relations.
         */
        $this->fillCollectionWithRelations($collection);
    }

}