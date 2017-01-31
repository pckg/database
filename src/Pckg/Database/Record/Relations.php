<?php namespace Pckg\Database\Record;

trait Relations
{

    protected $relations = [];

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

    public function relationExists($key)
    {
        return array_key_exists($key, $this->relations);
    }

    public function getRelationIfSet($name)
    {
        if (!isset($this->relations[$name])) {
            return null;
        }

        return $this->relations[$name];
    }

    public function setRelation($key, $value)
    {
        $this->relations[$key] = $value;

        return $this;
    }

    public function getRelation($key)
    {
        return $this->relations[$key];
    }

    public function getRelations()
    {
        return $this->relations;
    }

}