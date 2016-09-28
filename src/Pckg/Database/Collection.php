<?php namespace Pckg\Database;

use Pckg\Database\Entity\EntityInterface;

class Collection extends \Pckg\Collection
{

    public function setEntity(EntityInterface $entity)
    {
        $this->each(
            function(Record $record) use ($entity) {
                $record->setEntity($entity);
                $record->setEntityClass(get_class($entity));
            }
        );

        return $this;
    }

    public function setSaved($saved = true)
    {
        $this->each(
            function(Record $record) use ($saved) {
                $record->setSaved($saved);
            }
        );

        return $this;
    }

}