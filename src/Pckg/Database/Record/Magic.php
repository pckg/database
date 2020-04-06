<?php namespace Pckg\Database\Record;

use Pckg\Database\Helper\Convention;
use Pckg\Database\Relation\Helper\CallWithRelation;

/**
 * Class Magic
 *
 * @package Pckg\Database\Record
 */
trait Magic
{

    use CallWithRelation;

    /**
     * @var array
     */
    protected $encapsulate = [];

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function getEncapsulated($key, $value)
    {
        $encapsulator = $this->encapsulate[$key] ?? null;
        if (!$encapsulator) {
            return $value;
        }

        return (new $encapsulator($value));
    }

    /**
     * @param $key
     *
     * @return bool
     */
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
            return $this->getEncapsulated($key, $this->getValue($key));
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
            //$relation = $this->callWithRelation($key, [], $entity);
            $relation = $entity->{$key}();

            if (!$relation) {
                return null;
            }

            $relation->fillRecord($this);

            return $this->getRelation($relation->getFill());
        }

        /**
         * Return value from extension.
         */
        if ($chains = $this->getEntityChains($entity, $key, '__get')) {
            return chain($chains);
        }

        /**
         * Return value from advanced relation.
         */
        if (method_exists($entity, 'get' . ucfirst($key) . 'Subquery')) {
            $subqueryEntity = $entity->{'get' . ucfirst($key) . 'Subquery'}();
            $groupBy = $subqueryEntity->getQuery()->getGroupBy();
            if ($field = end(explode('.', $groupBy))) {
                $subqueryEntity->where($field, $this->id);
            }
            $result = $subqueryEntity->one()->data();
            $field = end($result);
            $this->data[$key] = $field;

            return $field;
        }

        return null;
    }

    /**
     * @param $key
     * @param $val
     *
     * @return $this
     */
    public function __set($key, $val)
    {
        /**
         * Set value via setter
         */
        if (method_exists($this, 'set' . Convention::toPascal($key) . 'Attribute')) {
            return $this->{'set' . Convention::toPascal($key) . 'Attribute'}($val);
        }

        if (!$this->ready) {
            $this->data[$key] = $val;
        } else if (array_key_exists($key, $this->data)) {
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

    /**
     *
     *
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $entity = $this->getEntity();

        if (strpos($method, 'create') === 0) {
            $relation = $entity->{lcfirst(substr($method, '6'))}();

            /**
             * This is currently working for has many relations.
             * $conversation->createMessage();
             */
            return $relation->getRightEntity()->getRecord(
                array_merge(
                    [
                        $relation->getForeignKey() => $this->id,
                    ],
                    $args[0] ?? []
                )
            );
        } else {
            /*$relation = null;
            $tempArgs = [];
            if (strpos($method, 'required') === 0) {
                $rel = lcfirst(substr($method, strlen('required')));
                if ($this->relationExists($rel)) {
                    $rData = $this->getRelation($rel);

                    if (isset($args[0])) {
                        $args[0]($rData);
                    }

                    return $rData;
                } else {
                    $method = str_replace('required', 'with', $method);
                    $tempArgs = $args;
                    $args = [];
                }
            }*/

            message(static::class . '.' . $method . '()', 'optimize');

            /**
             * @T00D00 - with should be called only if method starts with 'join' or 'with'
             */
            $relation = $this->callWithRelation($method, $args, $entity);

            if (!$relation) {
                return null;
            }

            $data = $this->getRelation($relation->getFill());

            /*if ($tempArgs) {
                $tempArgs[0]($data);
            }*/

            return $data;
        }
    }

}