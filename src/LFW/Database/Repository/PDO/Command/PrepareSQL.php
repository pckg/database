<?php

namespace LFW\Database\Repository\PDO\Command;

use LFW\Database\Query;
use LFW\Database\Repository;

/**
 * Class PrepareSQL
 * @package LFW\Database\Repository\PDO\Command
 */
class PrepareSQL
{

    /**
     * @var
     */
    protected $query;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @param $sql
     * @param Repository $repository
     */
    public function __construct(Query $query, Repository $repository)
    {
        $this->query = $query;
        $this->repository = $repository;
    }

    /**
     * @return \PDOStatement
     */
    public function execute()
    {
        return $this->repository->getConnection()->prepare($this->query->buildSQL());
    }

}