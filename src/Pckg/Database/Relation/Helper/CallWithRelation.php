<?php namespace Pckg\Database\Relation\Helper;

use Pckg\Collection;
use Pckg\Database\Record;

trait CallWithRelation
{

    protected function callWithRelation($method, $args, $entity)
    {
        $relation = $entity->callWith($method, $args, $entity, true);

        if (!$relation && prod()) {
            return null;
        }

        if ($this instanceof Collection) {
            $relation->fillCollection($this);

        } else if ($this instanceof Record) {
            $relation->fillRecord($this);

        }

        return $relation;
    }

}