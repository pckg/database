<?php namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Record;
use Pckg\Database\Relation;

class MorphedBy extends MorphsMany
{

    public function fillRecord(Record $record)
    {
        $rightForeignKey = $this->rightForeignKey;
        $leftForeignKey = $this->leftForeignKey;

        $morphKey = $this->morph;
        $polyKey = $this->poly;

        $middleEntity = $this->getMiddleEntity();
        $rightEntity = $this->getRightEntity();

        $leftCollectionKey = $this->getLeftCollectionKey();
        $rightCollectionKey = $this->fill;

        // get records from middle (mtm) entity
        message(
            'MorphedBy: getting middle collection ' . get_class(
                $middleEntity
            ) . ' ' . $morphKey . ' = ' . $record->id . ' (' . $leftForeignKey . ')'
        );
        $middleCollection = $this->getMiddleCollection($middleEntity, $morphKey, $record->id);

        // get right record ids and preset middle record with null values
        $arrRightIds = [];
        foreach ($middleCollection as $middleRecord) {
            $arrRightIds[$middleRecord->{$leftForeignKey}] = $middleRecord->{$leftForeignKey};
            $middleRecord->setRelation($rightForeignKey, $record);
            $middleRecord->setRelation($leftForeignKey, null);
        }

        // prepare record for mtm relation and right relation
        $record->setRelation($this->fill, $middleCollection);
        $record->setRelation($rightCollectionKey, new Collection());
        message($this->fill . ' - ' . $rightCollectionKey);

        if ($arrRightIds) {
            // get all right records
            message(
                'MorphedBy: getting right collection ' . get_class($rightEntity) . ' id ' . implode(',', $arrRightIds)
            );
            $rightCollection = $this->getRightCollection($rightEntity, 'id', $arrRightIds);

            // set relation
            message(
                'MorphedBy: setting record relation ' . $rightCollectionKey . ' (count: ' . $rightCollection->count(
                ) . ')'
            );
            $record->setRelation($rightCollectionKey, $rightCollection);

            // we also have to fill it with relations
            $this->fillCollectionWithRelations($record->getRelation($rightCollectionKey));

            // we need to link middle record with left and right records
            foreach ($rightCollection as $rightRecord) {
                foreach ($middleCollection as $middleRecord) {
                    if ($middleRecord->{$leftForeignKey} == $rightRecord->id) {
                        $middleRecord->setRelation($leftForeignKey, $rightRecord);
                        $rightRecord->setRelation($leftCollectionKey, $middleRecord);
                        break;
                    }
                }
            }
        }

        // also fill current relation's relations
        $this->fillRecordWithRelations($record);
    }

}