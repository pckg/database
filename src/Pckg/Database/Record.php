<?php

namespace Pckg\Database;

use Pckg\Framework\Helper\Reflect;

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
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        $entity = new $this->entity;

        if (method_exists($entity, $key)) {
            dd('Method ' . $key . ' exists in ' . $this->entity);
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

        //db(5);
        dd($this, 'Method ' . $key . ' doesnt exist in ' . $this->entity);

        return null;
    }

    public function keyExists($key)
    {
        return array_key_exists($key, $this->values);
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