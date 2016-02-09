<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Repository;

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
     * @param Entity     $entity
     * @param Repository $repository
     */
    public function __construct(Entity $entity, Repository $repository = null)
    {
        $this->entity = $entity;
        $this->repository = $repository
            ?: $this->entity->getRepository();
    }

    /**
     * Prepare query from entity, fetch records and fill them with relations.
     * @return Collection
     */
    public function executeAll()
    {
        $repository = $this->repository;
        $entity = $this->entity;
        $prepare = $repository->prepareQuery($entity->getQuery(), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $results = $repository->fetchAllPrepared($prepare)) {
            return $entity->fillCollectionWithRelations(new Collection($results));
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
        $prepare = $repository->prepareQuery($entity->getQuery()->limit(1), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $record = $repository->fetchPrepared($prepare)) {
            return $entity->fillRecordWithRelations($record);
        }

        return null;
    }

}