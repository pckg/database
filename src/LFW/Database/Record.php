<?php

namespace LFW\Database;

use LFW\Reflect;

/**
 * Class Record
 * @package LFW\Database
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
     * @param Entity|null $entity
     * @param Repository|null $repository
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
     * @param Entity|null $entity
     * @param Repository|null $repository
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
     * @param Entity|null $entity
     * @param Repository|null $repository
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