<?php

namespace Pckg\Database\Record;

use Pckg\Collection;
use Pckg\Database\Record;

/**
 * Class Relations
 *
 * @package Pckg\Database\Record
 */
trait Relations
{
    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @return bool
     */
    public function hasRelation($key)
    {
        if (array_key_exists($key, $this->relations)) {
            return true;
        }

        $entity = $this->getEntity();
        if (method_exists($entity, $key)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function relationExists($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * @return null|Collection|Record
     */
    public function getRelationIfSet($name)
    {
        return array_key_exists($name, $this->relations)
            ? $this->relations[$name]
            : null;
    }

    /**
     * @return $this
     */
    public function setRelation($key, $value)
    {
        $this->relations[$key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetRelation($key)
    {
        unset($this->relations[$key]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRelation($key)
    {
        return $this->relations[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }
}
