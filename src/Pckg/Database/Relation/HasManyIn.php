<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Collection;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class HasManyIn
 *
 * @package Pckg\Database\Relation
 */
class HasManyIn extends HasMany
{
    /**
     * @param CollectionInterface $collection
     */
    public function fillCollection(CollectionInterface $collection)
    {
        /**
         * Prepare relations on left records.
         */
        $collection->each(
            function (Record $record) {
                $record->setRelation($this->fill, new Collection());
            }
        );

        /**
         *
         */
        [$primaryKeyCallable, $primaryKey] = ($this->primaryKey)();
        $cacheMapper = $collection->keyBy($primaryKey)->map(function (Record $record) use ($primaryKey, $primaryKeyCallable) {
            $cacheMapper[$record->{$primaryKey}] = $primaryKeyCallable($record);

            return $cacheMapper[$record->{$primaryKey}];
        });

        /**
         * Get records from right entity.
         */
        $rightCollection = $this->getRightCollection(
            $this->getRightEntity(),
            $this->foreignKey,
            $cacheMapper->flat()->unique()
        );

        /**
         * Key collection for simpler processing.
         */
        $keyedCollection = $collection->keyBy($primaryKey);

        /**
         * Set relations on left records.
         */
        $keyedRightCollection = $rightCollection->keyBy($this->foreignKey);
        $cacheMapper->each(function ($keys, $id) use ($keyedCollection, $keyedRightCollection) {
            foreach ($keys as $i) {
                if (!($keyedRightCollection[$i] ?? null)) {
                    continue; // not index?
                }
                $keyedCollection[$id][$this->fill]->push($keyedRightCollection[$i]);
            }
        });
        /*$rightCollection->each(
            function($rightRecord) use ($keyedCollection) {
                if ($keyedCollection->hasKey($rightRecord->{$this->foreignKey})) {
                    $keyedCollection[$rightRecord->{$this->foreignKey}]->getRelation($this->fill)->push($rightRecord);
                }
            }
        );*/

        /**
         * Fill relations.
         */
        $this->fillCollectionWithRelations($collection);
    }
}
