<?php

namespace LFW\Database\Query\Helper;

use Closure;
use Exception;
use LFW\Database\Collection;
use LFW\Database\Record;
use LFW\Database\Relation;
use LFW\Reflect;

trait With
{

    /**
     * @var array
     */
    protected $autocall = [
        'withRequired',
        'with',
        'join',
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
    public function with(Relation $relation)
    {
        $this->with[] = $relation;

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

    protected function fillWithRecord(Record $record)
    {
        foreach ($this->getWith() as $relation) {
            $relation->fillRecord($record);
        }
    }

    protected function fillWithCollection(Collection $collection)
    {
        foreach ($this->getWith() as $relation) {
            d(get_class($relation) . '->' . get_class($relation->getRightEntity()));
            $relation->fillCollection($collection);
        }
    }


}