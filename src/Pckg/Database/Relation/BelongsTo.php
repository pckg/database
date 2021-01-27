<?php namespace Pckg\Database\Relation;

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

    /**
     * @param CollectionInterface $collection
     */
    public function fillCollection(CollectionInterface $collection)
    {
        /**
         * Prepare relations on left records.
         */
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
            $this->filterLeft
                ? $collection->filter($this->filterLeft)->map($this->foreignKey)->removeEmpty()->unique()
                : $collection->map($this->foreignKey)->removeEmpty()->unique()
        );

        /**
         * Key collection for simpler processing.
         */
        $keyedRightCollection = $rightCollection->keyBy($this->primaryKey);

        /**
         * Set relations on left records.
         */
        $collection->each(
            function($record) use ($keyedRightCollection) {
                if ($keyedRightCollection->hasKey($record->{$this->foreignKey})) {
                    $record->setRelation($this->fill, $keyedRightCollection[$record->{$this->foreignKey}]);
                }
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
        $rightEntity = $this->getRightEntity();
        $leftEntity = $this->getLeftEntity();
        $rightAlias = $rightEntity->getAlias() ?? $rightEntity->getTable();
        $leftAlias = $leftEntity->getAlias() ?? $leftEntity->getTable();

        $condition = $this->join . ' `' . $rightEntity->getTable() . '` AS `' . $rightAlias . '`' .
                     ($this->primaryKey && $this->foreignKey
                         ? ' ON `' . $leftAlias . '`.`' . $this->foreignKey . '`' .
                           ' = `' . $rightAlias . '`.`' . $this->primaryKey . '`'
                         : '');

        return $condition;
    }

}