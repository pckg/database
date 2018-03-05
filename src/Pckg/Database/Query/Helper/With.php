<?php namespace Pckg\Database\Query\Helper;

use Closure;
use Exception;
use Pckg\CollectionInterface;
use Pckg\Concept\Reflect;
use Pckg\Database\Record;
use Pckg\Database\Relation;

/**
 * Class With
 *
 * @package Pckg\Database\Query\Helper
 */
trait With
{

    /**
     * @var array
     */
    protected $autocallPrefixes = [
        'withRequired',
        'with',
        'join',
        'leftJoin',
        'where',
    ];

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @param $method
     * @param $args
     *
     * @return $this|Relation|mixed
     * @throws Exception
     */
    public function callWith($method, $args, $object, $returnRelation = false)
    {
        if (is_string($object)) {
            $object = Reflect::create($object);
        }

        /**
         * Check if $method prefix is listed in autocallPrefixes.
         */
        foreach ($this->autocallPrefixes as $prefix) {
            if (substr($method, 0, strlen($prefix)) === $prefix
                && strtoupper(substr($method, strlen($prefix), 1)) == substr($method, strlen($prefix), 1)
            ) {
                /**
                 * We are calling relation function without arguments: $entity->something();.
                 */
                $relationMethod = lcfirst(substr($method, strlen($prefix)));
                $relation = Reflect::method($object, $relationMethod, $args);
                // $relation = $object->{$relationMethod}();

                if (isset($args[0]) && ($args[0] instanceof Closure || is_only_callable($args[0]))) {
                    /**
                     * If callback was added, we run it.
                     * Now, this is a problem if we're making join because query was already merged.
                     * So, we'll call this magically and provide both - relation and query.
                     *
                     * @T00D00
                     */
                    if (in_array($prefix, ['join', 'leftJoin', 'innerJoin'])) {
                        $rightEntity = $relation->getRightEntity();
                        $oldEntityQuery = $rightEntity->getQuery();
                        $rightEntity->setQuery($relation->getQuery());
                        Reflect::call($args[0], [$relation, $relation->getQuery()]);
                        $rightEntity->setQuery($oldEntityQuery);
                    } else {
                        Reflect::call($args[0], [$relation, $relation->getQuery()]);
                    }
                }

                if ($prefix == 'leftJoin') {
                    $relation->leftJoin();
                }
                /**
                 * We'll call $entity->with($relation), $entity->join($relation) or $entity->required($relation), and return Relation;
                 */
                $return = $object->{$prefix == 'leftJoin' ? 'join' : $prefix}($relation);

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

        if (isset($args[0]) && ($args[0] instanceof Closure || is_only_callable($args[0]))) {
            Reflect::call($args[0], [$relation, $relation->getQuery()]);
        }

        return $relation;
    }

    /**
     *
     */
    public function withRequired(Relation $relation)
    {
        $this->with($relation);

        return $this;
    }

    /**
     *
     */
    public function with($relation, $callback = null)
    {
        if ($relation == $this) {
            /**
             * Why is this here?
             */
            return $this;
        }

        if (is_only_callable($callback)) {
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
     * Fill record with all it's relations.
     *
     * @param Record $record
     *
     * @return Record
     */
    public function fillRecordWithRelations(Record $record)
    {
        foreach ($this->getWith() as $relation) {
            $relation->fillRecord($record);
        }

        return $record;
    }

    /**
     * @return array
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * Fill collection of records with all of their relations.
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function fillCollectionWithRelations(CollectionInterface $collection)
    {
        if (!$collection->count()) {
            return $collection;
        }

        foreach ($this->getWith() as $relation) {
            $relation->fillCollection($collection);
        }

        return $collection;
    }

}