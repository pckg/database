<?php namespace Pckg\Database\Record;

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
     * @param $key
     *
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
     * @param $key
     *
     * @return bool
     */
    public function relationExists($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function getRelationIfSet($name)
    {
        if (!isset($this->relations[$name])) {
            return null;
        }

        return $this->relations[$name];
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setRelation($key, $value)
    {
        $this->relations[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getRelation($key)
    {
        return $this->relations[$key];
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

}