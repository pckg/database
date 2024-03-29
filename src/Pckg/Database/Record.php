<?php

namespace Pckg\Database;

use Carbon\Carbon;
use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record\Actions;
use Pckg\Database\Record\Events;
use Pckg\Database\Record\Extension\Deletable;
use Pckg\Database\Record\Extension\Permissionable;
use Pckg\Database\Record\Extension\Translatable;
use Pckg\Database\Record\Magic;
use Pckg\Database\Record\Relations;
use Pckg\Database\Record\Transformations;

/**
 * Class Record
 *
 * @package Pckg\Database
 * @property int|string|null $id
 */
class Record extends Obj
{
    use Magic;
    use Actions;
    use Relations;
    use Transformations;
    use Events;

    use Deletable;
    use Translatable;
    use Permissionable;

    protected $entity = Entity::class;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var bool
     */
    protected $ready = false;

    /**
     * @param array $values
     */
    final public function __construct($data = [], Entity $entity = null)
    {
        $defaults = $this->data ?? [];
        $this->data = $data && is_array($data) ? $data : [];
        if ($defaults) {
            $this->data = array_merge($defaults, $this->data);
        }

        /**
         * Encapsulate into object.
         */
        foreach ($this->encapsulate as $key => $encapsulator) {
            if (!array_key_exists($key, $this->data)) {
                continue;
            }
            $this->data[$key] = new $encapsulator($this->data[$key], $key, $this);
        }

        if ($entity) {
            $this->entity = $entity;
        }

        $this->ready = true;
    }

    /**
     * @param          $key
     * @param callable $val
     *
     * @return mixed
     */
    public function cache($key, callable $val)
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $val();
        }

        return $this->cache[$key];
    }

    /**
     * @return mixed
     */
    public function cacheDecodedField($dataKey)
    {
        $decodedKey = 'decoded' . ucfirst($dataKey);

        return $this->cache($decodedKey, function () use ($dataKey) {
            return json_decode($this->data($dataKey), true);
        });
    }

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

    /**
     * @return array
     */
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
                if (
                    substr($entity->getTable(), strlen($entity->getTable()) - strlen($suffix)) != $suffix
                    && $this->repository->getCache()->hasTable($entity->getTable() . $suffix)
                ) {
                    $keys[$entity->getTable() . $suffix] = $this->{$method}();
                }
            }
        }

        return $values;
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return is_object($this->entity)
            ? $this->entity
            : $this->entity = Reflect::create($this->getEntityClass());
    }

    /**
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

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
     * @return array
     */
    public function getToArrayValues()
    {
        return $this->getKeyValue($this->toArray);
    }

    /**
     * @return array
     */
    protected function getKeyValue($source)
    {
        $values = [];
        foreach ($source as $i => $j) {
            if (is_only_callable($j)) {
                $values = $values + $j($this, $values);
                continue;
            }

            $key = is_int($i) ? $j : $i;
            $getter = $j;

            $type = null;
            if (substr($getter, 0, 1) === '+') {
                $type = 'plus'; // add if existent - should be "always add"!
                $getter = substr($getter, 1);
            } elseif (substr($getter, 0, 1) === '?') {
                $type = 'question'; // add if existent?
                $getter = substr($getter, 1);
            } else if (strpos($getter, '-') === 0) {
                // always remove
                continue;
            }

            if (substr($key, 0, 1) === '+') {
                $type = 'plus'; // add if existent - should be "always add"!
                $key = substr($key, 1);
            } else if (substr($key, 0, 1) === '?') {
                $type = 'question'; // add if existent?
                $key = substr($key, 1);
            } else if (strpos($key, '-') === 0) {
                // always remove
                continue;
            }

            if ($this->hasKey($getter)) {
                /**
                 * Key exist in original or extended tables.
                 */
                if ($type && !$this->keyExists($getter)) {
                    continue;
                }
                $values[$key] = $this->{$getter};
            } elseif ($this->hasRelation($getter)) {
                /**
                 * Relation exists in entity definition.
                 */
                if ($type && !$this->relationExists($getter)) {
                    if ($type === 'plus') {
                        $values[$key] = $this->{$getter};
                    }
                    continue;
                }
                $values[$key] = $this->getRelationIfSet($getter);
            } elseif (!$type && method_exists($this, 'get' . Convention::toPascal($getter) . 'Attribute')) {
                /**
                 * Getter exists in record definition.
                 */
                $values[$key] = $this->{'get' . Convention::toPascal($getter) . 'Attribute'}();
            }
        }

        return $values;
    }

    /**
     * @return bool
     */
    public function hasKey($key)
    {
        if (array_key_exists($key, $this->data)) {
            return true;
        }

        $entity = $this->getEntity();
        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable(), $key)) {
            return true;
        }

        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable() . '_i18n', $key)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getToJsonValues()
    {
        return $this->getKeyValue($this->toJson);
    }

    /**
     * @return $this
     */
    public function setEntityClass($class)
    {
        $this->entity = $class;

        return $this;
    }

    /**
     * @return Entity
     */
    public function getEntityIfEmpty($entity)
    {
        if ($entity) {
            return $entity;
        }

        return $this->getEntity();
    }

    /**
     * @return Entity
     */
    public function prepareEntityIfEmpty($entity)
    {
        if ($entity) {
            return $entity;
        }

        return $this->prepareEntity();
    }

    /**
     * @return Entity
     */
    public function prepareEntity()
    {
        return Reflect::create($this->getEntityClass());
    }

    /**
     * @param null $key
     *
     * @return array|mixed|null
     * @deprecated
     */
    public function getData($key = null)
    {
        if (!$key) {
            return $this->data;
        } else if ($this->hasKey($key)) {
            return $this->data[$key] ?? null;
        }

        return null;
    }

    /**
     * @param Entity $entity
     * @param        $key
     * @param        $overloadMethod
     *
     * @return array
     */
    private function getEntityChains(Entity $entity, $key, $overloadMethod)
    {
        $chains = [];
        foreach (get_class_methods($entity) as $method) {
            if (substr($method, 0, strlen($overloadMethod)) == $overloadMethod && substr($method, -9) == 'Extension') {
                $chains[] = function () use ($method, $entity, $key) {
                    return $entity->$method($this, $key);
                };
            }
        }

        return $chains;
    }

    /**
     * Makes LOCK IN SHARE MODE
     */
    public function lock()
    {
        $entity = $this->prepareEntity()->where('id', $this->id);
        //$entity->getQuery()->lock();
        return $entity->oneOrFail();
    }
}
