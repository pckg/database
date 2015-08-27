<?php

namespace Pckg\Database\Relation;

use Pckg\Database\Collection;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class BelongsTo
 * @package Pckg\Database\Relation
 */
class BelongsTo extends Relation
{

    public function getRightForeignKey()
    {
        $class = explode('\\', get_class($this->getRightEntity()));

        return lcfirst(Convention::nameOne(array_pop($class))) . '_id';
    }

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->getRightForeignKey();

        if ($record->{$rightForeignKey}) {
            $record->{substr($rightForeignKey, 0, -3)} = $this->getRightEntity()->where('id', $record->{$rightForeignKey})->one();
        } else {
            $record->{substr($rightForeignKey, 0, -3)} = null;
        }

        $this->fillWithRecord($record);
    }

    public function fillCollection(Collection $collection)
    {
        $arrIds = [];

        $rightForeignKey = $this->getRightForeignKey();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                $arrIds[$record->{$rightForeignKey}] = $record->{$rightForeignKey};
                $record->{substr($rightForeignKey, 0, -3)} = new Collection();
            }
        }

        $rightEntity = $this->getRightEntity();
        $foreignCollection = $rightEntity->where($rightEntity->getPrimaryKey(), $arrIds, 'IN')->all();
        foreach ($collection as $record) {
            if ($record->{$rightForeignKey}) {
                foreach ($foreignCollection as $foreignRecord) {
                    if ($foreignRecord->id == $record->{$rightForeignKey}) {
                        $record->{substr($rightForeignKey, 0, -3)} = $foreignRecord;
                        break 2;
                    }
                }
            }
        }

        $this->fillWithCollection($collection);
    }

}