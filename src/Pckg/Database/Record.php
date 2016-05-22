<?php

namespace Pckg\Database;

use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;

/**
 * Class Record
 * @package Pckg\Database
 */
class Record extends Object
{

    /**
     * @var
     */
    protected $entity = Entity::class;

    protected $relations = [];

    public function hasKey($key)
    {
        return $this->__isset($key);
    }

    public function __isset($key)
    {
        if (method_exists($this, 'get' . ucfirst(Convention::toCamel($key)))) {
            return true;
        }

        if ($this->keyExists($key)) {
            return true;
        }

        if ($this->relationExists($key)) {
            return true;
        }

        $entity = $this->getEntity();

        if (method_exists($entity, $key)) {
            return true;
        }

        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            return true;
        }

        foreach (get_class_methods($entity) as $method) {
            if (substr($method, 0, 7) == '__isset' && substr($method, -9) == 'Extension') {
                if ($entity->$method($key)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function __get($key)
    {
        $entity = $this->getEntity();

        /**
         * Return value via getter
         */
        if ($key != 'value' && method_exists($this, 'get' . ucfirst(Convention::toCamel($key)))) {
            return $this->{'get' . ucfirst(Convention::toCamel($key))}();
        }

        /**
         * Return value from existing relation (Collection/Record).
         */
        if ($this->relationExists($key)) {
            return $this->getRelation($key);
        }

        /**
         * Return value, even if it's null or not set.
         */
        if ($this->keyExists($key) || $entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            return $this->getValue($key);
        }

        /**
         * Return value from empty relation.
         */
        if (method_exists($entity, $key)) {
            $relation = $entity->{$key}();

            $relation->fillRecord($this);

            return $this->getRelation($relation->getFill());
        }

        /**
         * Return value from extension.
         */
        if ($chains = $this->getEntityChains($entity, $key)) {
            return chain($chains);
        }

        dd('Method (key) ' . $key . ' doesnt exist in ' . get_class($entity) . ' (entity table is ' . $entity->getTable() . ') called from __get ' . get_class($this), db(8));
    }

    public function __call($method, $args)
    {
        /**
         * Return value from empty relation.
         */
        $entity = $this->getEntity();
        $relation = $entity->callWith($method, $args, $entity, true);
        $relation->fillRecord($this, true);

        $data = $this->getRelation(lcfirst(substr($method, 4)));

        return $data;
    }

    private function getEntityChains(Entity $entity, $key)
    {
        $chains = [];
        foreach (get_class_methods($entity) as $method) {
            if (substr($method, 0, 5) == '__get' && substr($method, -9) == 'Extension') {
                $chains[] = function () use ($method, $entity, $key) {
                    return $entity->$method($this, $key);
                };
            }
        }

        return $chains;
    }

    /**
     * @return array
     */
    public function __toArray($values = null, $depth = 5)
    {
        $return = [];

        if (!$depth) {
            return;
        }

        if (!$values) {
            $values = $this->data;
        }

        foreach ($values as $key => $value) {
            if (is_object($value)) {
                $return[$key] = $this->__toArray($value->__toArray(), $depth - 1);
            } else if (is_array($value)) {
                $return[$key] = $this->__toArray($value, $depth - 1);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    public function relationExists($key)
    {
        return array_key_exists($key, $this->relations);
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

    public function setEntityClass($class)
    {
        $this->entity = $class;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityClass()
    {
        return $this->entity;
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return is_object($this->entity)
            ? $this->entity
            : Reflect::create($this->getEntityClass());
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return mixed
     */
    public function prepareEntity()
    {
        return Reflect::create($this->getEntityClass());
    }

    /**
     * @return Record
     */
    public function save(Entity $entity = null, Repository $repository = null)
    {
        if ($this->id) {
            return $this->update($entity, $repository);

        } else {
            return $this->insert($entity, $repository);

        }
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function update(Entity $entity = null, Repository $repository = null)
    {
        if (!$entity) {
            $entity = $this->prepareEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        return $repository->update($this, $entity);
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function delete(Entity $entity = null, Repository $repository = null)
    {
        if (!$entity) {
            $entity = $this->prepareEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        return $repository->delete($this, $entity);
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function insert(Entity $entity = null, Repository $repository = null)
    {
        if (!$entity) {
            $entity = $this->prepareEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        return $repository->insert($this, $entity);
    }

}