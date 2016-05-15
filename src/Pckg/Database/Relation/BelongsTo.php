<?php

namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class BelongsTo
 * @package Pckg\Database\Relation
 */
class BelongsTo extends Relation
{

    public function mergeToQuery(Select $query)
    {
        $condition = '';
        if ($this->condition) {
            $condition = ' AND ' . implode(' AND ', $this->condition);
        }

        $query->join($this->getKeyCondition() . $condition);

        foreach ($this->select as $select) {
            $query->prependSelect($select);
        }

        return $this;
    }

    /**
     * @param Record $record
     *
     * @return mixed
     *
     * Example: layout belongs to variable.
     */
    public function fillRecord(Record $record)
    {
        $foreignKey = $this->getForeignKey();
        $rightEntity = $this->getRightEntity();

        if ($record->{$foreignKey}) {
            $record->setRelation($this->fill,
                (new GetRecords($rightEntity->where('id', $record->{$foreignKey})))->executeOne());
        } else {
            $record->setRelation($this->fill, null);
        }

        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(Collection $collection)
    {
        if (!$collection->count()) {
            message('no count');
            return $collection;
        } else {
            message(
                'filling collection of ' . get_class($collection->first()) . ' : ' .
                $this->getLeftEntity()->getTable() . '.' . $this->getPrimaryKey() . ' = ' . $this->getRightEntity()->getTable() . '.' . $this->getForeignKey()
            );
        }

        $arrIds = [];

        $rightForeignKey = $this->getForeignKey();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                $arrIds[$record->{$rightForeignKey}] = $record->{$rightForeignKey};
                $record->setRelation($this->fill, null);
            }
        }

        $rightEntity = clone $this->getRightEntity();
        $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrIds);
        foreach ($collection as $record) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->id == $record->{$rightForeignKey}) {
                    $record->setRelation($this->fill, $foreignRecord);
                    break;
                }
            }
        }
    }

}