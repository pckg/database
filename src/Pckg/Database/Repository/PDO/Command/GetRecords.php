<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use PDO;

/**
 * Class GetAllRecords
 * @package Pckg\Database\Repository\PDO\Command
 */
class GetRecords
{

    /**
     * @var Entity
     */
    protected $entity;

    protected $repository;

    /**
     * @param Entity $entity
     * @param Repository $repository
     */
    public function __construct(Entity $entity, Repository $repository = null)
    {
        $this->entity = $entity;
        $this->repository = $repository
            ?: $this->entity->getRepository();
    }

    protected function checkBinds(Query $query) {
        $binds = $query->getBinds();
        $sql = $query->buildSQL();
        $countBinds = 0;
        foreach ($binds as $group => $bs) {
            $countBinds += count($bs);
        }
        if (substr_count($sql, '?') != $countBinds) {
            dd($sql, $binds, substr_count($sql, '?'));
        }
    }

    /**
     * Prepare query from entity, fetch records and fill them with relations.
     * @return Collection
     */
    public function executeAll()
    {
        $repository = $this->repository;
        $entity = $this->entity;

        //$this->checkBinds($entity->getQuery());

        $prepare = $repository->prepareQuery($entity->getQuery(), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $results = $repository->fetchAllPrepared($prepare)) {
            $collection = new Collection($results);
            if ($entity->getQuery()->isCounted()) {
                $prepareCount = $repository->prepareSQL('SELECT FOUND_ROWS()');
                $repository->executePrepared($prepareCount);
                $collection->setTotal($prepareCount->fetch(PDO::FETCH_COLUMN));
            }
            $collection->setEntity($entity);

            return $entity->fillCollectionWithRelations($collection);

        }

        return new Collection();
    }

    /**
     * Prepare query from entity, fetch record and fill it with relations.
     * @return Record
     */
    public function executeOne()
    {
        $repository = $this->repository;
        $entity = $this->entity;

        //$this->checkBinds($entity->getQuery());

        $prepare = $repository->prepareQuery($entity->getQuery()->limit(1), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $record = $repository->fetchPrepared($prepare)) {
            $record->setEntity($entity);

            return $entity->fillRecordWithRelations($record);
        }

        return null;
    }

}