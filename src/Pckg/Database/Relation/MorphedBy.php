<?php namespace Pckg\Database\Relation;

use Pckg\CollectionInterface;
use Pckg\Database\Record;
use Pckg\Database\Relation;

class MorphedBy extends Relation
{

    function fillRecord(Record $record)
    {
        die("morphedBy");
        // TODO: Implement fillRecord() method.
    }

    function fillCollection(CollectionInterface $collection)
    {
        die("morphedBy");
        // TODO: Implement fillCollection() method.
    }

}