<?php namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Collection;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation\Helper\MiddleEntity;

/**
 * Class HasAndBelongTo
 *
 * @package Pckg\Database\Relation
 */
class HasAndBelongsTo extends HasMany
{

    use MiddleEntity;

    /**
     * @param Select $query
     */
    public function mergeToQuery(Select $query)
    {
        /**
         * Join middle entity
         */
        $middleQuery = $this->getMiddleEntity()->getQuery();

        $middleAlias = $middleQuery->getAlias() ?? $middleQuery->getTable();
        $query->join(
            $this->join . ' `' . $middleQuery->getTable() . '` AS `' . $middleAlias . '`',
            $this->getMiddleKeyCondition(),
            null,
            $this->getMiddleKeyBinds()
        );

        /**
         * Join right entity
         */
        $rightQuery = $this->getRightEntity()->getQuery();
        $rightAlias = $rightQuery->getAlias() ?? $rightQuery->getTable();
        $query->join(
            $this->join . ' `' . $rightQuery->getTable() . '` AS `' . $rightAlias . '`',
            '`' . $this->getRightEntity()->getTable() . '`.`id` = `' . $middleQuery->getTable() . '`.`' .
            $this->getRightForeignKey() . '`'
        );

        /**
         * Add select fields
         */
        foreach ($this->getMiddleEntity()->getQuery()->getSelect() as $key => $val) {
            if (is_numeric($key)) {
                $query->prependSelect([$val]);
            } else {
                $query->addSelect([$key => $val]);
            }
        }
        foreach ($this->getRightEntity()->getQuery()->getSelect() as $key => $val) {
            if (is_numeric($key)) {
                $query->prependSelect([$val]);
            } else {
                $query->addSelect([$key => $val]);
            }
        }
    }

    /**
     * @return string
     */
    public function getMiddleKeyCondition()
    {
        $middleQuery = $this->getMiddleEntity()->getQuery();

        return '`' . $this->getLeftEntity()->getTable() . '`.`id` = ' .
               '`' . $middleQuery->getTable() . '`.`' . $this->getLeftForeignKey() . '`';
    }

    /**
     * @return array
     */
    public function getMiddleKeyBinds()
    {
        return [];
    }

    /**
     * @param Record $record
     */
    public function fillRecord(Record $record)
    {
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

        /**
         * Key collections for simpler processing.
         */
        $keyedRightCollection = $rightCollection->keyBy($this->rightPrimaryKey);

        /**
         * Set middle collection's relations.
         */
        $middleCollection->each(
            function($middleRecord) use ($record, $keyedRightCollection) {
                if ($keyedRightCollection->hasKey($middleRecord->{$this->rightForeignKey})) {
                    /**
                     * We need to clone record, otherwise we override pivot relation each time.
                     */
                    $rightRecord = clone $keyedRightCollection[$middleRecord->{$this->rightForeignKey}];
                    $rightRecord->setRelation('pivot', $middleRecord);
                    $record->getRelation($this->fill)->push($rightRecord);
                }
            }
        );

        /**
         * This is needed for special case in Records.php:129
         */
        if ($after = $this->after) {
            $after($record);
        }

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

        /**
         * Key collections for simpler processing.
         */
        $keyedLeftCollection = $collection->keyBy($this->leftPrimaryKey);
        $keyedRightCollection = $rightCollection->keyBy($this->rightPrimaryKey);

        /**
         * Set middle collection's relations.
         * This should be sorted by right collection, right? :)
         */
        /*$middleCollection->each(
            function($middleRecord) use ($keyedLeftCollection, $keyedRightCollection) {
                if ($keyedRightCollection->hasKey($middleRecord->{$this->rightForeignKey})) {
                    $rightRecord = clone $keyedRightCollection[$middleRecord->{$this->rightForeignKey}];
                    $rightRecord->setRelation('pivot', $middleRecord);
                    $keyedLeftCollection[$middleRecord->{$this->leftForeignKey}]->getRelation($this->fill)->push(
                        $rightRecord
                    );
                }
            }
        );*/
        $groupedMiddleCollection = $middleCollection->groupBy($this->rightForeignKey);
        $keyedRightCollection->each(function($rightRecord) use (
            $keyedLeftCollection, $groupedMiddleCollection, $rightCollection
        ) {
            (new Collection($groupedMiddleCollection[$rightRecord->{$this->rightPrimaryKey}] ?? []))
                ->each(function($middleRecord) use ($rightRecord, $keyedLeftCollection) {
                    $rightRecord = clone $rightRecord;
                    $rightRecord->setRelation('pivot', $middleRecord);
                    $keyedLeftCollection[$middleRecord->{$this->leftForeignKey}]->getRelation($this->fill)->push(
                        $rightRecord
                    );
                });
        });

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

}