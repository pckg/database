<?php

namespace Pckg\Database;

use Exception;
use Pckg\Cache\Cache;
use Pckg\Database\Driver\DriverInterface;
use PDO;

/**
 * Interface Repository
 *
 * @package Pckg\Database
 */
interface Repository
{
    /**
     * @param Entity $entity
     *
     * @return Record
     */
    public function one(Entity $entity);

    /**
     * @param Entity $entity
     *
     * @return Collection
     * @throws Exception
     */
    public function all(Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function update(Record $record, Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function delete(Record $record, Entity $entity);

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function deleteTranslation(Record $record, Entity $entity, $language);

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return Record
     */
    public function insert(Record $record, Entity $entity);

    /**
     * @param Query $query
     * @param null  $recordClass
     *
     * @return mixed|\PDOStatement
     */
    public function prepareQuery(Query $query, $recordClass = null);

    /**
     * @return mixed
     */
    public function executePrepared($prepare);

    /**
     * @return mixed
     */
    public function fetchAllPrepared($prepare);

    /**
     * @return mixed
     */
    public function fetchPrepared($prepare);

    /**
     * @return Cache|\Pckg\Database\Helper\Cache
     */
    public function getCache();
}
