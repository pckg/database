<?php namespace Pckg\Database;

use Pckg\Database\Entity\EntityInterface;

class Collection extends \Pckg\Collection
{

    protected $total;
    
    public function setEntity(EntityInterface $entity) {
        $this->each(function(Record $record) use ($entity){
            $record->setEntity($entity);
            $record->setEntityClass(get_class($entity));
        });
    }

    public function total()
    {
        return $this->total ? $this->total : count($this->collection);
    }

    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

}