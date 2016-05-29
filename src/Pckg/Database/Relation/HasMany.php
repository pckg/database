<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class HasMany
 * @package Pckg\Database\Relation
 */
class HasMany extends Relation
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

    public function getLeftForeignKey()
    {
        $class = explode('\\', get_class($this->getLeftEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function fillRecord(Record $record, $debug = false)
    {
        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();
        $foreignCollection = $this->getForeignCollection($rightEntity, $foreignKey, $record->{$primaryKey});

        foreach ($foreignCollection as $foreignRecord) {
            $foreignRecord->setRelation($this->fill, new Collection());
        }

        $this->fillCollectionWithRelations($foreignCollection);

        $record->setRelation($this->fill, new Collection());
        foreach ($foreignCollection as $foreignRecord) {
            $record->getRelation($this->fill)->push($foreignRecord);
            $foreignRecord->{$this->fill} = $foreignRecord;
        }
    }

    public function fillCollection(Collection $collection)
    {
        $arrPrimaryIds = [];

        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();

        foreach ($collection as $primaryRecord) {
            $arrPrimaryIds[$primaryRecord->{$primaryKey}] = $primaryRecord->{$primaryKey};
            $primaryRecord->setRelation($this->fill, new Collection());
        }

        if ($arrPrimaryIds) {
            $foreignCollection = $this->getForeignCollection($rightEntity, $foreignKey, $arrPrimaryIds);
            foreach ($foreignCollection as $foreignRecord) {
                $foreignRecord->{$this->fill} = new Collection();
            }

            $this->fillCollectionWithRelations($foreignCollection);

            foreach ($collection as $primaryRecord) {
                foreach ($foreignCollection as $foreignRecord) {
                    /*
                     * Foreign records needs to have set entity with correct table because we'll read data from
                     * repository cache in __get method.
                     */
                    $foreignRecord->setEntity($rightEntity);

                    if ($primaryRecord->{$primaryKey} == $foreignRecord->{$foreignKey}) {
                        $primaryRecord->getRelation($this->fill)->push($foreignRecord);
                        $foreignRecord->{$this->fill} = $foreignRecord;
                    }
                }
            }
        }
    }

}