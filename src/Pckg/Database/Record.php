<?php namespace Pckg\Database;

use Carbon\Carbon;
use JsonSerializable;
use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Record\Actions;
use Pckg\Database\Record\Events;
use Pckg\Database\Record\Magic;
use Pckg\Database\Record\Relations;
use Pckg\Database\Record\Transformations;

/**
 * Class Record
 *
 * @package Pckg\Database
 */
class Record extends Object implements JsonSerializable
{

    use Magic, Actions, Relations, Transformations, Events;

    /**
     * @var
     */
    protected $entity = Entity::class;

    protected $toArray = [];

    protected $cache = [];

    /**
     * @var array
     * @T00D00
     */
    protected $bind = [
        'dt_added' => Carbon::class,
    ];

    public function cache($key, callable $val)
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $val();
        }

        return $this->cache[$key];
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
     * @param array $values
     */
    public function __construct($data = [], Entity $entity = null)
    {
        $this->data = $data ?? [];
        if ($entity) {
            $this->entity = $entity;
        }
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

        if ($entity->getRepository()->getCache()->tableHasField($entity->getTable() . '_i18n', $key)) {
            return true;
        }

        return false;
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
        return is_object($this->entity)
            ? $this->entity
            : $this->entity = Reflect::create($this->getEntityClass());
    }

    public function getEntityIfEmpty($entity)
    {
        if ($entity) {
            return $entity;
        }

        return $this->getEntity();
    }

    /**
     * @param $entity
     *
     * @return Entity
     */
    public function prepareEntityIfEmpty($entity)
    {
        if ($entity) {
            return $entity;
        }

        return $this->prepareEntity();
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

}