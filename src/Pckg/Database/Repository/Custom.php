<?php

namespace Pckg\Database\Repository;

use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class Custom
 * @package Pckg\Database\Repository
 */
class Custom implements Repository
{

    use Failable;

    /**
     * @param Entity $entity
     * @return null
     */
    public function one(Entity $entity)
    {
        return null;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    public function all(Entity $entity)
    {
        return [];
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @return Record
     */
    public function update(Record $record, Entity $entity)
    {
        // TODO: Implement update() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @return Record
     */
    public function delete(Record $record, Entity $entity)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
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
}