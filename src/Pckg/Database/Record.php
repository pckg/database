<?php

namespace Pckg\Database;

use Carbon\Carbon;
use JsonSerializable;
use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record\Magic;
use Pckg\Database\Record\RecordInterface;

/**
 * Class Record
 *
 * @package Pckg\Database
 */
class Record extends Object implements RecordInterface, JsonSerializable
{

    use Magic;

    /**
     * @var
     */
    protected $entity = Entity::class;

    protected $relations = [];

    protected $toArray = [];

    protected $saved = false;

    protected $deleted = false;

    /**
     * @var array
     * @T00D00
     */
    protected $bind = [
        'dt_added' => Carbon::class,
    ];

    /**
     * @return array
     * @T00D00
     */
    public function defaults()
    {
        return [
            'created_at'   => date('Y-m-d H:i:s'),
            'confirmed_at' => '0000-00-00 00:00:00',
        ];
    }

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

    public static function create($data = [])
    {
        $record = new static($data);

        $record->save();

        return $record;
    }

    public static function getOrCreate(array $data)
    {
        $entity = (new static)->getEntity();

        $record = $entity->whereArr($data)->one();

        if (!$record) {
            $record = static::create($data);
        }

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

    public function hasKey($key)
    {
        if (array_key_exists($key, $this->data)) {
            return true;
        }

        $entity = $this->getEntity();
        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            return true;
        }

        return false;
    }

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

    private function getEntityChains(Entity $entity, $key, $overloadMethod)
    {
        $chains = [];
        foreach (get_class_methods($entity) as $method) {
            if (substr($method, 0, strlen($overloadMethod)) == $overloadMethod && substr($method, -9) == 'Extension') {
                $chains[] = function() use ($method, $entity, $key) {
                    return $entity->$method($this, $key);
                };
            }
        }

        return $chains;
    }

    public function getExtensionValues()
    {
        $values = [];

        return $values;

        $entity = $this->getEntity();
        foreach (get_class_methods($entity) as $method) {
            /**
             * Get extension's fields.
             */
            $keys = [];
            if ($method != 'getFields' && substr($method, 0, 3) == 'get' && substr($method, -6) == 'Fields') {
                $suffix = $entity->{'get' . substr($method, 3, -6) . 'TableSuffix'}();
                if (!$suffix) {
                    continue;
                }
                if (substr($entity->getTable(), strlen($entity->getTable()) - strlen($suffix)) != $suffix
                    && $this->repository->getCache()->hasTable($entity->getTable() . $suffix)
                ) {
                    $keys[$entity->getTable() . $suffix] = $this->{$method}();
                }
            }
        }

        return $values;
    }

    public function getToArrayValues()
    {
        $values = [];
        foreach ($this->toArray as $key) {
            if ($this->hasKey($key)) {
                $values[$key] = $this->{$key};

            } elseif ($this->hasRelation($key)) {
                $values[$key] = $this->getRelationIfSet($key);

            } elseif (method_exists($this, 'get' . Convention::toPascal($key) . 'Attribute')) {
                $values[$key] = $this->{'get' . Convention::toPascal($key) . 'Attribute'}();

            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function __toArray($values = null, $depth = 6, $withToArray = true)
    {
        $return = [];

        if (!$depth) {
            return [];
        }

        if (is_null($values)) {
            $values = $this->data;
            if ($withToArray && $this->toArray) {
                foreach ($this->getToArrayValues() as $key => $value) {
                    $values[$key] = $value;
                }
                foreach ($this->getExtensionValues() as $key => $value) {
                    $values[$key] = $value;
                }
            }
        }

        foreach ($values as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, '__toArray')) {
                    $return[$key] = $value->__toArray(null, $depth - 1, $withToArray);

                } else {
                    $return[$key] = (string)$value;

                }

            } else if (is_array($value)) {
                $return[$key] = $this->__toArray($value, $depth - 1, $withToArray);

            } else {
                $return[$key] = $value;

            }
        }

        return $return;
    }

    public function toJSON()
    {
        return json_encode($this->jsonSerialize());
    }

    function jsonSerialize()
    {
        return $this->__toArray();
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
        return is_object($this->entity)
            ? get_class($this->entity)
            : $this->entity;
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        if (!is_object($this->entity)) {
            //d("Not object", $this->getEntityClass(), get_class($this), $this->entity);
        }

        return is_object($this->entity)
            ? $this->entity
            : $this->entity = Reflect::create($this->getEntityClass());
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
            $entity = $this->getEntity();
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
            $entity = $this->getEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        return $repository->insert($this, $entity);
    }

    public function refetch()
    {
        $entity = $this->getEntity();
        foreach ($this->data as $key => $val) {
            $entity->where($key, $val);
        }
        $record = $entity->one();

        if ($record) {
            $this->data = $record->getData();
        }

        return $this;
    }

    public function getData($key = null)
    {
        if (!$key) {
            return $this->data;

        } else if ($this->hasKey($key)) {
            return $this->data[$key] ?? null;

        }

        return null;
    }

}