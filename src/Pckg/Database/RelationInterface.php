<?php namespace Pckg\Database;

use Pckg\CollectionInterface;

interface RelationInterface
{
    
    public function fillRecord(Record $record);

    public function fillRecordWithRelations(Record $record);

    public function fillCollection(CollectionInterface $collection);

    public function fillCollectionWithRelations(CollectionInterface $collection);

    public function join($table, $on = null, $where = null);

    public function with($relation);

}