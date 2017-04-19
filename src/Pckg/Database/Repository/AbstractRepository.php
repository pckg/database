<?php namespace Pckg\Database\Repository;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO\Command\GetRecords;

abstract class AbstractRepository implements Repository
{

    protected $connection;

    protected $cache;

    /**
     * @param \PDO $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    abstract public function getCache();

    /**
     * @param Entity $entity
     *
     * @return mixed
     */
    public function one(Entity $entity)
    {
        return (new GetRecords($entity, $this))->executeOne();
    }

    /**
     * @param Entity $entity
     *
     * @return mixed
     */
    public function all(Entity $entity)
    {
        return (new GetRecords($entity, $this))->executeAll();
    }

    public function executePrepared($prepare)
    {
        $execute = $prepare->execute();

        return $execute;
    }

    public function fetchAllPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function() use ($prepare) {
                return $prepare->fetchAll();
            }
        );
    }

    public function fetchPrepared($prepare)
    {
        return $prepare->fetch();
    }

}