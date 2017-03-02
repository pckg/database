<?php namespace Pckg\Database\Record;

use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Repository;

trait Actions
{

    protected $saved = false;

    protected $deleted = false;

    public function isSaved()
    {
        return $this->saved;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function setSaved($saved = true)
    {
        $this->saved = $saved;

        return $this;
    }

    public function setDeleted($deleted = true)
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function setAndSave($key, $val = null)
    {
        $this->set($key, $val);
        $this->save();
    }

    /**
     * @return Record
     */
    public function save(Entity $entity = null, Repository $repository = null)
    {
        if ($this->isSaved()) {
            $this->update($entity, $repository);

        } else {
            $this->insert($entity, $repository);

        }

        return $this;
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function update(Entity $entity = null, Repository $repository = null)
    {
        $entity = $this->prepareEntityIfEmpty($entity);
        $repository = $entity->getRepositoryIfEmpty($repository);

        $this->trigger(['saving', 'updating']);

        $update = $repository->update($this, $entity);

        $this->trigger(['updated', 'saved']);

        return $update;
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function delete(Entity $entity = null, Repository $repository = null)
    {
        $entity = $this->getEntityIfEmpty($entity);
        $repository = $entity->getRepositoryIfEmpty($repository);

        $this->trigger(['saving', 'deleting']);

        $delete = $repository->delete($this, $entity);

        $this->trigger(['deleted', 'saved']);

        return $delete;
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function insert(Entity $entity = null, Repository $repository = null)
    {
        $entity = $this->getEntityIfEmpty($entity);
        $repository = $entity->getRepositoryIfEmpty($repository);

        $this->trigger(['saving', 'inserting']);

        $insert = $repository->insert($this, $entity);

        $this->trigger(['inserted', 'saved']);

        return $insert;
    }

    public function refetch()
    {
        $entity = $this->getEntity();
        foreach ($this->data as $key => $val) {
            $entity->where($key, $val);
        }
        $record = $entity->one();

        if ($record) {
            $this->data = $record->data();
        }

        return $this;
    }

    public function duplicate(Entity $entity = null)
    {
        $entity = $this->getEntityIfEmpty($entity);

        $data = $this->toArray();
        if (isset($data['id'])) {
            unset($data['id']);
        }

        $record = new static($data);
        $record->save($entity);

        return $record;
    }

    public function updateIf($data)
    {
        $updated = false;
        foreach ($data as $key => $val) {
            if ($val) {
                $this->{$key} = $val;
                $updated = true;
            }
        }

        if ($updated) {
            $this->save();
        }

        return $this;
    }

    public static function create($data = [], Entity $entity = null)
    {
        $record = new static($data, $entity);

        $record->save();

        return $record;
    }

    public static function getOrCreate(array $data, Entity $entity = null)
    {
        if (!$entity) {
            $entity = (new static)->getEntity();
        }

        $record = $entity->whereArr($data)->one();

        if (!$record) {
            $record = static::create($data, $entity);
        }

        return $record;
    }

    /**
     * @param array         $data
     * @param Entity|null   $entity
     * @param callable|null $callable
     *
     * @return mixed|Record
     */
    public static function getOrFail(array $data, Entity $entity = null, callable $callable = null)
    {
        if (!$entity) {
            $entity = (new static)->getEntity();
        }

        $record = $entity->whereArr($data)->oneOrFail($callable);

        return $record;
    }

}