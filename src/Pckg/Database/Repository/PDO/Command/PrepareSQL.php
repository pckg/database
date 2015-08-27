<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Database\Query;
use Pckg\Database\Repository;

/**
 * Class PrepareSQL
 * @package Pckg\Database\Repository\PDO\Command
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