<?php

namespace Pckg\Database\Query\Helper;

use Closure;
use Exception;
use Pckg\Collection;
use Pckg\CollectionInterface;
use Pckg\Concept\Reflect;
use Pckg\Database\Record;
use Pckg\Database\Relation;

trait With
{

    /**
     * @var array
     */
    protected $autocallPrefixes = [
        'withRequired',
        'with',
        'join',
        'where',
    ];

    protected $with = [];

    /**
     * @param $method
     * @param $args
     *
     * @return $this|Relation
     * @throws Exception
     */
    public function callWith($method, $args, $object, $returnRelation = false)
    {
        if (is_string($object)) {
            $object = Reflect::create($object);
        }

        foreach ($this->autocallPrefixes as $prefix) {
            if (substr($method, 0, strlen($prefix)) == $prefix) {
                /**
                 * We are calling relation function without arguments: $entity->withSomething();.
                 */
                $relation = $object->{lcfirst(substr($method, strlen($prefix)))}();

                /**
                 * We'll return $entity->with($relation), which is Relation;
                 */
                $return = $this->{$prefix}($relation);

                /**
                 * If callback was added, we run it.
                 */
                if (isset($args[0]) && ($args[0] instanceof Closure || is_callable($args[0]))) {
                    $args[0]($relation);
                }

                /**
                 * Then we return relation.
                 */
                return $returnRelation
                    ? $relation
                    : $return;
            }
        }

        if (!method_exists($object, $method)) {
            throw new Exception('Method ' . $method . ' does not exist in ' . get_class($object));
        }

        $relation = Reflect::method($object, $method, $args);

        if (isset($args[0]) && ($args[0] instanceof Closure || is_callable($args[0]))) {
            $args[0]($relation);
        }

        return $relation;
    }

    public function getWith()
    {
        return $this->with;
    }

    /**
     *
     */
    public function with($relation)
    {
        if ($relation == $this) {
            return $this;
        }

        if ($relation instanceof Relation) {
            $this->with[] = $relation;

        } else {
            $this->with[] = $this->{$relation}();

        }

        return $this;
    }

    /**
     *
     */
    public function withRequired(Relation $relation)
    {
        $this->with($relation);

        return $this;
    }

    public function fillRecordWithRelations(Record $record)
    {
        foreach ($this->getWith() as $relation) {
             $relation->fillRecord($record);
        }

        return $record;
    }

    public function fillCollectionWithRelations(CollectionInterface $collection)
    {
        foreach ($this->getWith() as $relation) {
            $relation->fillCollection($collection);
        }

        return $collection;
    }


}