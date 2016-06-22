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

                if (isset($args[0]) && ($args[0] instanceof Closure || is_callable($args[0]))) {
                    /**
                     * If callback was added, we run it.
                     * Now, this is a problem if we're making join because query was already merged.
                     * So, we'll call this magically and provide both.
                     */
                    Reflect::call($args[0], [$relation, $relation->getQuery()]);
                }
                /**
                 * We'll return $entity->with($relation), which is Relation;
                 */
                $return = $this->{$prefix}($relation);

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

        /**
         * Autoprefixes failed, return relation, probably?
         */
        $relation = Reflect::method($object, $method, $args);

        if (isset($args[0]) && ($args[0] instanceof Closure || is_callable($args[0]))) {
            Reflect::call($args[0], [$relation, $relation->getQuery()]);
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
    public function with($relation, $callback = null)
    {
        if ($relation == $this) {
            return $this;
        }

        if (is_callable($callback)) {
            Reflect::call($callback, [$relation, $relation->getQuery()]);
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