<?php

namespace Pckg\Database\Relation;

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

    public function fillRecord(Record $record, $debug = false) {
        $primaryKey = $this->primaryKey;
        $foreignKey = $this->foreignKey;
        $rightEntity = $this->getRightEntity();

        if ($record->{$primaryKey}) {
            $record->setRelation(
                $this->fill,
                $this->getForeignRecord($rightEntity, $foreignKey, $record->{$primaryKey})
            );

        } else {
            $record->setRelation($this->fill, null);
        }
    }

    public function fillCollection(CollectionInterface $collection) {
        if (!$collection->count()) {
            return $collection;
        }

        $arrPrimaryIds = [];
        $rightEntity = $this->getRightEntity();

        $primaryKey = $this->primaryKey;
        $foreignKey = $this->foreignKey;

        foreach ($collection as $primaryRecord) {
            if ($primaryRecord->{$primaryKey}) {
                $arrPrimaryIds[$primaryRecord->{$primaryKey}] = $primaryRecord->{$primaryKey};
                $primaryRecord->setRelation($this->fill, null);
            }
        }

        if ($arrPrimaryIds) {
            $foreignCollection = $this->getForeignCollection($rightEntity, $foreignKey, $arrPrimaryIds);
            $foreignCollection->setEntity($rightEntity);
            $this->fillCollectionWithRelations($foreignCollection);

            message('HasOne: ' . $collection->count() . ' x ' . $foreignCollection->count());
            foreach ($collection as $primaryRecord) {
                foreach ($foreignCollection as $foreignRecord) {
                    if ($primaryRecord->{$primaryKey} == $foreignRecord->{$foreignKey}) {
                        $primaryRecord->setRelation($this->fill, $foreignRecord);
                        break;
                    }
                }
            }
        }
    }

}