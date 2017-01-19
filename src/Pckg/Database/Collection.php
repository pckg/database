<?php namespace Pckg\Database;

class Collection extends \Pckg\Collection
{

    public function setEntity(Entity $entity)
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

    public function setOriginalFromData()
    {
        $this->each(
            function(Record $record) {
                $record->setOriginalFromData();
            }
        );

        return $this;
    }

}