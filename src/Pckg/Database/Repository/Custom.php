<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class Custom
 *
 * @package Pckg\Database\Repository
 */
class Custom implements Repository
{
    use Failable;

    public function aliased()
    {
        return $this;
    }

    public function getName()
    {
        return static::class;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        $key = 'pckg.database.repository.cache.' . sha1(Cache::getCachePathByRepository($this));
        $context = context();

        if (!$context->exists($key)) {
            $context->bind($key, $cache = new Cache($this));
        }

        return $context->get($key);
    }

    /**
     * @param Entity $entity
     *
     * @return null
     */
    public function one(Entity $entity)
    {
        $data = $entity->getCustomRepositoryCollection();
        $query = $entity->getQuery();

        return $data->first(function (Record $record) use ($query) {
            $where = $query->getWhere();
            if (!$where) {
                return true;
            }

            $children = $where->getChildren();
            $binds = $query->getBinds('where');

            foreach ($children as $i => $child) {
                if (!is_string($child)) {
                    continue;
                }

                if (preg_match('/^`[a-z]*` = \?$/', $child, $matches)) {
                    $field = substr($child, 1, strpos($child, '`', 1) - 1);
                    $value = $binds[$i];

                    return $record->{$field} == $value;
                } else {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        return $entity->getCustomRepositoryCollection();
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function update(Record $record, Entity $entity)
    {
        // TODO: Implement update() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function delete(Record $record, Entity $entity)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function insert(Record $record, Entity $entity)
    {
        // TODO: Implement insert() method.
    }

    /**
     * @param $connection
     */
    public function setConnection($connection)
    {
        // TODO: Implement setConnection() method.
    }

    /**
     *
     */
    public function getConnection()
    {
        // TODO: Implement getConnection() method.
    }

    /**
     * @param Query $query
     * @param null  $recordClass
     */
    public function prepareQuery(Query $query, $recordClass = null)
    {
        // TODO: Implement prepareQuery() method.
    }

    /**
     * @param $prepare
     */
    public function executePrepared($prepare)
    {
        // TODO: Implement executePrepared() method.
    }

    /**
     * @param $prepare
     */
    public function fetchAllPrepared($prepare)
    {
        // TODO: Implement fetchAllPrepared() method.
    }

    /**
     * @param $prepare
     */
    public function fetchPrepared($prepare)
    {
        // TODO: Implement fetchPrepared() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @param        $language
     */
    public function deleteTranslation(Record $record, Entity $entity, $language)
    {
        // TODO: Implement deleteTranslation() method.
    }

    public function executeOne(Entity $entity)
    {
        return $this->one($entity);
    }

    public function executeAll(Entity $entity)
    {
        return $this->all($entity);
    }
}
