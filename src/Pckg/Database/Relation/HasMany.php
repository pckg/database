<?php namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Collection;
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

    /**
     * @param Record $record
     */
    public function fillRecord(Record $record)
    {
        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->foreignKey,
            $record->{$this->primaryKey}
        );

        /**
         * Set relation.
         */
        $record->setRelation($this->fill, $rightCollection);

        /**
         * Fill relations.
         */
        $this->fillRecordWithRelations($record);
    }

    /**
     * @param CollectionInterface $collection
     */
    public function fillCollection(CollectionInterface $collection)
    {
        /**
         * Prepare relations on left records.
         */
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