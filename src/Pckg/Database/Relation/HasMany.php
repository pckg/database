<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Select;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class HasMany
 * @package Pckg\Database\Relation
 */
class HasMany extends Relation
{

    protected $primaryKey;

    protected $primaryCollectionKey;

    protected $foreignKey;

    protected $foreignCollectionKey;

    public function primaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    public function foreignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    public function primaryCollectionKey($primaryCollectionKey)
    {
        $this->primaryCollectionKey = $primaryCollectionKey;

        return $this;
    }

    public function foreignCollectionKey($foreignCollectionKey)
    {
        $this->foreignCollectionKey = $foreignCollectionKey;

        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey
            ? $this->primaryKey
            : $this->getLeftForeignKey();
    }

    public function getForeignKey()
    {
        return $this->foreignKey
            ? $this->foreignKey
            : $this->getRightForeignKey();
    }

    public function getPrimaryCollectionKey()
    {
        return $this->primaryCollectionKey
            ? $this->primaryCollectionKey
            : Convention::nameOne($this->getLeftForeignKey());
    }

    public function getForeignCollectionKey()
    {
        return $this->foreignCollectionKey
            ? $this->foreignCollectionKey
            : Convention::nameMultiple($this->getRightForeignKey());
    }

    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function getLeftForeignKey()
    {
        $class = explode('\\', get_class($this->getLeftEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function fillRecord(Record $record)
    {
        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();
        $rightEntity->resetQuery();

        $primaryCollectionKey = $this->getPrimaryCollectionKey();
        $foreignCollectionKey = $this->getForeignCollectionKey();
        $foreignCollection = (new GetRecords($rightEntity->where($foreignKey, $record->{$primaryKey})))->executeAll();

        foreach ($foreignCollection as $foreignRecord) {
            $foreignRecord->{$primaryCollectionKey} = new Collection();
        }

        $this->fillCollectionWithRelations($foreignCollection);

        foreach ($foreignCollection as $foreignRecord) {
            $record->{$foreignCollectionKey}->push($foreignRecord);
            $foreignRecord->{$primaryCollectionKey} = $foreignRecord;
        }
    }

    public function fillCollection(Collection $collection)
    {
        $arrPrimaryIds = [];

        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $this->getForeignKey();

        $rightEntity = $this->getRightEntity();
        $rightEntity->resetQuery();

        $primaryCollectionKey = $this->getPrimaryCollectionKey();
        $foreignCollectionKey = $this->getForeignCollectionKey();
        foreach ($collection as $record) {
            $arrPrimaryIds[$record->{$primaryKey}] = $record->{$primaryKey};
            $record->{$foreignCollectionKey} = new Collection();
        }

        if ($arrPrimaryIds) {
            $foreignCollection = (new GetRecords($rightEntity->where($foreignKey, $arrPrimaryIds, 'IN')))->executeAll();
            foreach ($foreignCollection as $foreignRecord) {
                $foreignRecord->{$primaryCollectionKey} = new Collection();
            }

            $this->fillCollectionWithRelations($foreignCollection);

            foreach ($collection as $primaryRecord) {
                foreach ($foreignCollection as $foreignRecord) {
                    if ($primaryRecord->{$primaryKey} == $foreignRecord->{$foreignKey}) {
                        $primaryRecord->{$foreignCollectionKey}->push($foreignRecord);
                        $foreignRecord->{$primaryCollectionKey} = $foreignRecord;
                    }
                }
            }
        }
    }
}