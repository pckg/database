<?php

namespace Pckg\Database;

use JsonSerializable;
use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record\RecordInterface;

/**
 * Class Record
 *
 * @package Pckg\Database
 */
class Record extends Object implements RecordInterface, JsonSerializable
{

    /**
     * @var
     */
    protected $entity = Entity::class;

    protected $relations = [];

    protected $toArray = [];

    public function __set($key, $val)
    {
        if (array_key_exists($key, $this->data)) {
            /**
             * Fill value to existing data.
             */
            $this->data[$key] = $val;

        } else if (array_key_exists($key, $this->relations)) {
            /**
             * Fill value to existing relation.
             */
            $this->relations[$key] = $val;

        } else if ($this->hasKey($key)) {
            /**
             * Fill value to new data.
             */
            $this->data[$key] = $val;

        } else if ($this->hasRelation($key)) {
            /**
             * Fill value to existing relation.
             */
            $this->relations[$key] = $val;

        } else {
            $this->data[$key] = $val;

        }

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

    public function __isset($key)
    {
        if (method_exists($this, 'get' . Convention::toPascal($key) . 'Attribute')) {
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
        if (!$key) {
            dd("no key", debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        }

        /**
         * Return value via getter
         */
        if (method_exists($this, 'get' . Convention::toPascal($key) . 'Attribute')) {
            return $this->{'get' . Convention::toPascal($key) . 'Attribute'}();
        }

        /**
         * Return value, even if it's null or not set.
         */
        if ($this->keyExists($key)) {
            return $this->getValue($key);
        }

        /**
         * Return value from existing relation (Collection or Record).
         */
        if ($this->relationExists($key)) {
            return $this->getRelation($key);
        }

        /**
         * @T00D00 - Entity is needed here just for cache, optimize this ... :/
         *         $Reflection = new ReflectionProperty(get_class($a), 'a');
         */
        $entity = $this->getEntity();
        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            return $this->getValue($key);
        }

        /**
         * Return value from empty relation.
         *
         * @T00D00 - optimize this
         */
        if (method_exists($entity, $key)) {
            $relation = $entity->{$key}();

            $relation->fillRecord($this);

            return $this->getRelation($relation->getFill());
        }

        /**
         * Return value from extension.
         */
        if ($chains = $this->getEntityChains($entity, $key, '__get')) {
            return chain($chains);
        }

        return null;

        dd(
            'Method (key) ' . $key . ' doesnt exist in ' . get_class(
                $entity
            ) . ' (entity table is ' . $entity->getTable() . ') called from __get ' . get_class($this),
            db(8)
        );
    }

    public function __call($method, $args)
    {
        /**
         * Return value from empty relation.
         */
        $entity = $this->getEntity();
        $relation = $entity->callWith($method, $args, $entity, true);
        /**
         * THis is not needed here?
         */
        // $relation->fill($method);
        $relation->fillRecord($this);

        $data = $this->getRelation($relation->getFill());

        return $data;
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

    /**
     * @return array
     */
    public function __toArray($values = null, $depth = 6, $withToArray = true)
    {
        $return = [];

        if (!$depth) {
            return [];
        }

        if (!$values) {
            $values = $this->data;
            if ($withToArray && $this->toArray) {
                foreach ($this->toArray as $key) {
                    if ($this->hasKey($key)) {
                        $values[$key] = $this->{$key};

                    } elseif ($this->hasRelation($key)) {
                        $values[$key] = $this->getRelationIfSet($key);

                    } elseif (method_exists($this, 'get' . Convention::toPascal($key) . 'Attribute')) {
                        $values[$key] = $this->{'get' . Convention::toPascal($key) . 'Attribute'}();

                    }
                }
            }
        }

        foreach ($values as $key => $value) {
            if (is_object($value)) {
                $return[$key] = $value->__toArray(null, $depth - 1, $withToArray);

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
        /**
         * @T00D00
         */
        if (isset($this->data['id'])) {
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