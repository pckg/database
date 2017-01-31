<?php namespace Pckg\Database;

use Pckg\Database\Query\Helper\With;
use Pckg\Database\Relation\Helper\CallWithRelation;

class Collection extends \Pckg\Collection
{

    use With, CallWithRelation;

    public function __call($method, $args)
    {
        if (!$this->count()) {
            return $this;
        }

        $this->callWithRelation($method, $args, $this->first()->getEntity());

        return $this;
    }

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