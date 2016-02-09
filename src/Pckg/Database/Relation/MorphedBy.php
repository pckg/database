<?php namespace Pckg\Database\Relation;

use Pckg\Collection;
use Pckg\Database\Record;
use Pckg\Database\Relation;

class MorphedBy extends Relation
{

    function fillRecord(Record $record)
    {
        die("morphedBy");
        // TODO: Implement fillRecord() method.
    }

    function fillCollection(Collection $collection)
    {
        die("morphedBy");
        // TODO: Implement fillCollection() method.
    }

}