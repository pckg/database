<?php namespace Pckg\Database\Record;

use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class Actions
 *
 * @package Pckg\Database\Record
 */
trait Actions
{

    /**
     * @var bool
     */
    protected $saved = false;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @param array       $data
     * @param Entity|null $entity
     *
     * @return $this|mixed|Record
     */
    public static function getOrCreate(array $data, Entity $entity = null)
    {
        $record = static::gets($data, $entity);

        if (!$record) {
            $record = static::create($data, $entity);
        }

        return $record;
    }

    /**
     * @param array       $data
     * @param Entity|null $entity
     *
     * @return mixed|Record
     */
    public static function gets(array $data, Entity $entity = null)
    {
        if (!$entity) {
            $entity = (new static)->getEntity();
        }

        return $entity->whereArr($data)->one();
    }

    /**
     * @param array       $data
     * @param Entity|null $entity
     *
     * @return $this|Record
     */
    public static function create($data = [], Entity $entity = null)
    {
        $record = new static($data, $entity);

        $record->save();

        return $record;
    }

    /**
     * @param array       $data
     * @param Entity|null $entity
     *
     * @return mixed|Record
     */
    public static function getOrNew(array $data, Entity $entity = null)
    {
        $record = static::gets($data, $entity);

        if (!$record) {
            $record = new static($data, $entity);
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

    /**
     * @return bool
     */
    public function isNew()
    {
        return !$this->saved && !$this->id;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return $this
     */
    public function setDeleted($deleted = true)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @param      $key
     * @param null $val
     *
     * @return $this
     */
    public function setAndSave($key, $val = null)
    {
        $this->set($key, $val);
        $this->save();

        return $this;
    }

    /**
     * @return Record|mixed
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
     * @return bool
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * @param bool $saved
     *
     * @return $this
     */
    public function setSaved($saved = true)
    {
        $this->saved = $saved;

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
    public function insert(Entity $entity = null, Repository $repository = null)
    {
        $entity = $this->getEntityIfEmpty($entity);
        $repository = $entity->getRepositoryIfEmpty($repository);

        $this->trigger(['saving', 'inserting']);

        $insert = $repository->insert($this, $entity);

        $this->trigger(['inserted', 'saved']);

        return $insert;
    }

    /**
     * @param array $overwrite
     *
     * @return mixed
     */
    public function saveAs($overwrite = [])
    {
        $data = $this->data();
        $data['id'] = null;
        $data = array_merge($data, $overwrite);

        return (new static($data))->save();
    }

    /**
     * @return mixed
     */
    public function delete(Entity $entity = null, Repository $repository = null)
    {
        return $this->getEntity()->isDeletable()
            ? $this->softDelete($entity, $repository)
            : $this->forceDelete($entity, $repository);
    }

    /**
     * @return mixed
     */
    public function softDelete(Entity $entity = null, Repository $repository = null)
    {
        $this->trigger(['softDeleting']);

        $this->deleted_at = date('Y-m-d H:i:s');

        $this->trigger(['softDeleted']);

        return $this->update($entity, $repository);
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return Record
     */
    public function forceDelete(Entity $entity = null, Repository $repository = null)
    {
        $entity = $this->getEntityIfEmpty($entity);
        $repository = $entity->getRepositoryIfEmpty($repository);

        $this->trigger(['saving', 'deleting']);

        $delete = $repository->delete($this, $entity);

        $this->trigger(['deleted', 'saved']);

        return $delete;
    }

    /**
     * @return $this
     */
    public function refetch()
    {
        $entity = $this->getEntity();
        if (!$this->id) {
            $entity->where('id', $this->id);
        } else {
            foreach ($this->data as $key => $val) {
                $entity->where($key, $val);
            }
        }
        $record = $entity->one();

        if ($record) {
            $this->data = $record->data();
        }

        return $this;
    }

    /**
     * @param Entity|null $entity
     *
     * @return static
     */
    public function duplicate(Entity $entity = null)
    {
        $entity = $this->getEntityIfEmpty($entity);

        $data = $this->toArray();
        if (isset($data['id'])) {
            unset($data['id']);
        }

        foreach ($data as $key => &$val) {
            if ($key && array_key_exists($key . '_x', $data) && array_key_exists($key . '_y', $data)) {
                $val = [
                    $data[$key . '_x'],
                    $data[$key . '_y'],
                ];
            }
        }

        $record = new static($data);
        $record->save($entity);

        return $record;
    }

    /**
     * @param $data
     *
     * @return $this
     */
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

}