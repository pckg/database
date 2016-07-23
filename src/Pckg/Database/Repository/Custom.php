<?php

namespace Pckg\Database\Repository;

use Pckg\Collection;
use Pckg\Database\Entity;
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

    /**
     * @param Entity $entity
     *
     * @return null
     */
    public function one(Entity $entity)
    {
        return null;
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        return [];
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

    public function prepareQuery(Query $query, $recordClass = null)
    {
        // TODO: Implement prepareQuery() method.
    }

    public function executePrepared($prepare)
    {
        // TODO: Implement executePrepared() method.
    }

    public function fetchAllPrepared($prepare)
    {
        // TODO: Implement fetchAllPrepared() method.
    }

    public function fetchPrepared($prepare)
    {
        // TODO: Implement fetchPrepared() method.
    }

    public function getCache()
    {
        // TODO: Implement getCache() method.
    }
}