<?php

namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

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
    public function fillRecord(Record $record) {
        $foreignKey = $this->foreignKey;
        $rightEntity = $this->getRightEntity();

        if ($record->{$foreignKey}) {
            $record->setRelation(
                $this->fill,
                (new GetRecords($rightEntity->where('id', $record->{$foreignKey})))->executeOne()
            );
        } else {
            $record->setRelation($this->fill, null);
        }

        $this->fillRecordWithRelations($record);
    }

    public function fillCollection(CollectionInterface $collection) {
        if (!$collection->count()) {
            return $collection;
        }

        $arrPrimaryIds = [];
        $rightEntity = $this->getRightEntity();

        $foreignKey = $this->foreignKey;
        $primaryKey = $this->primaryKey;

        foreach ($collection as $record) {
            if ($record->{$foreignKey}) {
                $arrPrimaryIds[$record->{$foreignKey}] = $record->{$foreignKey};
                $record->setRelation($this->fill, null);
            }
        }

        $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrPrimaryIds);
        foreach ($collection as $primaryRecord) {
            foreach ($foreignCollection as $foreignRecord) {
                if ($foreignRecord->{$primaryKey} == $primaryRecord->{$foreignKey}) {
                    $primaryRecord->setRelation($this->fill, $foreignRecord);
                    break;
                }
            }
        }

        $this->fillCollectionWithRelations($foreignCollection);

        if ($this->after) {
            $collection->each($this->after);
        }
    }

    /**
     * @T00D00 - join type needs to be dynamic!
     *
     * @return string
     */
    public function getKeyCondition()
    {
        return $this->join . ' `' . $this->getRightEntity()->getTable() . '`' .
               ' ON `' . $this->getLeftEntity()->getTable() . '`.`' . $this->foreignKey . '`' .
               ' = `' . $this->getRightEntity()->getTable() . '`.`' . $this->primaryKey . '`';
    }

}