<?php

namespace Pckg\Database\Query\Helper;

use Closure;
use Exception;
use Pckg\Collection;
use Pckg\Database\Record;
use Pckg\Database\Relation;
use Pckg\Concept\Reflect;

trait With
{

    /**
     * @var array
     */
    protected $autocall = [
        'withRequired',
        'with',
        'join',
        'where'
    ];

    protected $with = [];

    /**
     * @param $method
     * @param $args
     * @return $this
     * @throws Exception
     */
    public function callWith($method, $args, $object)
    {
        if (is_string($object)) {
            $object = Reflect::create($object);
        }

        foreach ($this->autocall as $autocall) {
            if (substr($method, 0, strlen($autocall)) == $autocall) {
                $relation = $object->{lcfirst(substr($method, strlen($autocall)))}();

                $return = $this->{$autocall}($relation);

                if (isset($args[0]) && $args[0] instanceof Closure) {
                    $args[0]($relation);
                }

                return $return;
            }
        }

        throw new Exception('Method ' . $method . ' doesn\'t exist in ' . static::class);
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
        $this->with[] = $relation;

        return $this;
    }

    public function fillRecordWithRelations(Record $record)
    {
        foreach ($this->getWith() as $relation) {
            $relation->fillRecord($record);
        }

        return $record;
    }

    public function fillCollectionWithRelations(Collection $collection)
    {
        foreach ($this->getWith() as $relation) {
            $relation->fillCollection($collection);
        }

        return $collection;
    }


}