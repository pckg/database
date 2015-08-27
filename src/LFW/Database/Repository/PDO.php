<?php

namespace LFW\Database\Repository;

use LFW\Database\Entity;
use LFW\Database\Helper\Cache;
use LFW\Database\Record;
use LFW\Database\Repository;
use LFW\Database\Repository\PDO\Command\DeleteRecord;
use LFW\Database\Repository\PDO\Command\GetRecords;
use LFW\Database\Repository\PDO\Command\InsertRecord;
use LFW\Database\Repository\PDO\Command\UpdateRecord;

/**
 * Class PDO
 * @package LFW\Database\Repository
 */
class PDO implements Repository
{

    use Failable;

    /**
     * @var
     */
    protected $connection;

    protected $cache;

    /**
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection)
    {
        $this->setConnection($connection);
        $this->cache = new Cache($this);
    }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param \PDO $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param Entity $entity
     * @return mixed
     */
    public function one(Entity $entity)
    {
        return (new GetRecords($entity, $this))->executeOne();
    }

    /**
     * @param Entity $entity
     * @return mixed
     */
    public function all(Entity $entity)
    {
        return (new GetRecords($entity, $this))->executeAll();
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @return $this
     */
    public function update(Record $record, Entity $entity)
    {
        (new UpdateRecord($record, $entity, $this))->execute();

        return $this;
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @return $this
     */
    public function insert(Record $record, Entity $entity)
    {
        (new InsertRecord($record, $entity, $this))->execute();

        return $this;
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @return $this
     */
    public function delete(Record $record, Entity $entity)
    {
        (new DeleteRecord($record, $entity, $this))->execute();

        return $this;
    }

}