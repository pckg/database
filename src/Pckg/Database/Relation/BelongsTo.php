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
        $primaryKey = $this->primaryKey;
        $rightEntity = $this->getRightEntity();

        if ($record->{$foreignKey}) {
            $record->setRelation(
                $this->fill,
                (new GetRecords($rightEntity->where($primaryKey, $record->{$foreignKey})))->executeOne()
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

        foreach ($collection as $primaryRecord) {
            if ($primaryRecord->{$foreignKey}) {
                $arrPrimaryIds[$primaryRecord->{$foreignKey}] = $primaryRecord->{$foreignKey};
                $primaryRecord->setRelation($this->fill, null);
            }
        }

        if ($arrPrimaryIds) {
            $foreignCollection = $this->getForeignCollection($rightEntity, $rightEntity->getPrimaryKey(), $arrPrimaryIds);
            $foreignCollection->setEntity($rightEntity);
            $this->fillCollectionWithRelations($foreignCollection);

            message('BelongsTo: ' . $collection->count() . ' x ' . $foreignCollection->count());
            foreach ($collection as $primaryRecord) {
                foreach ($foreignCollection as $foreignRecord) {
                    if ($primaryRecord->{$foreignKey} == $foreignRecord->{$primaryKey}) {
                        $primaryRecord->setRelation($this->fill, $foreignRecord);
                        break;
                    }
                }
            }

            if ($this->after) {
                $collection->each($this->after);
            }
        }
    }

    /**
     * @T00D00 - join type needs to be dynamic!
     *
     * @return string
     */
    public function getKeyCondition() {
        return $this->join . ' `' . $this->getRightEntity()->getTable() . '`' .
               ' ON `' . $this->getLeftEntity()->getTable() . '`.`' . $this->foreignKey . '`' .
               ' = `' . $this->getRightEntity()->getTable() . '`.`' . $this->primaryKey . '`';
    }

}