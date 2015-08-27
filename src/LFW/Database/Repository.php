<?php

namespace LFW\Database;

use Exception;
use LFW\Database\Collection;
use PDO;

/**
 * Interface Repository
 * @package LFW\Database
 */
interface Repository
{

    /**
     * @param $connection
     * @return mixed
     */
    public function setConnection($connection);

    /**
     * @return PDO
     */
    public function getConnection();

    /**
     * @param Entity $entity
     * @return Record
     */
    public function one(Entity $entity);

    /**
     * @param Entity $entity
     * @return Collection
     * @throws Exception
     */
    public function all(Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     * @return Record
     */
    public function update(Record $record, Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     * @return Record
     */
    public function delete(Record $record, Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     * @return Record
     */
    public function insert(Record $record, Entity $entity);

}