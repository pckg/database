<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO\Command\GetRecords;

/**
 * Class AbstractRepository
 *
 * @package Pckg\Database\Repository
 */
abstract class AbstractRepository implements Repository
{

    /**
     * @var
     */
    protected $connection;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param            $alias
     * @param Repository $repository
     *
     * @return $this
     */
    public function addAlias($alias, Repository $repository)
    {
        $this->aliases[$alias] = $repository;

        return $this;
    }

    /**
     * @param $alias
     *
     * @return mixed|AbstractRepository
     */
    public function aliased($alias)
    {
        return $this->aliases[$alias] ?? $this;
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
     * @return mixed
     */
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

    /**
     * @param $prepare
     *
     * @return mixed
     */
    public function executePrepared($prepare)
    {
        $execute = $prepare->execute();

        return $execute;
    }

    /**
     * @param $prepare
     *
     * @return mixed
     */
    public function fetchAllPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function () use ($prepare) {
                return $prepare->fetchAll();
            }
        );
    }

    /**
     * @param $prepare
     *
     * @return mixed
     */
    public function fetchPrepared($prepare)
    {
        return $prepare->fetch();
    }
}
