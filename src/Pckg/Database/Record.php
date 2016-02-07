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

    /**
     * @var
     */
    protected $pk;

    /**
     * @param $key
     *
     * @return null
     */
    public function __get($key)
    {
        $entity = $this->getEntity();

        if ($this->keyExists($key) || $entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            if (method_exists($this, 'get' . ucfirst(Convention::toCamel($key)))) {
                return $this->{'get' . ucfirst(Convention::toCamel($key))}();
            }

            return $this->getValue($key);
        }

        if (method_exists($entity, $key)) {
            $relation = $entity->{$key}();

            $relation->fillRecord($this);

            return $this->getValue($key);
        }

        foreach (get_class_methods($entity) as $method) {
            $chains = [];

            if (substr($method, 0, 5) == '__get' && substr($method, -9) == 'Extension') {
                $chains[] = function () use ($method, $entity, $key) {
                    //d('Returning value from ' . get_class($this) . ' ' . $key . ' ' . get_class($entity));
                    return $entity->$method($this, $key);
                };
            }

            if ($chains) {
                return chain($chains);
            }
        }

        db(8);
        dd('Method ' . $key . ' doesnt exist in ' . get_class($entity) . ' (entity table is ' . $entity->getTable() . ') called from __get ' . get_class($this));

        return null;
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
            $values = $this->values;
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

    public function keyExists($key)
    {
        return array_key_exists($key, $this->values);
    }

    public function getValue($key)
    {
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : null;
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
     * @return mixed
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
    public function getPK()
    {
        return $this->pk;
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
    public function save()
    {
        if ($this->id) {
            return $this->update();
        } else {
            return $this->insert();
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