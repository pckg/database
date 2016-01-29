<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Collection;
use Pckg\Database\Entity;
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
        $prepare = $this->repository->getConnection()->prepare($this->entity->getQuery());
        $prepare->setFetchMode(PDO::FETCH_CLASS, $this->entity->getRecordClass());

        if ($execute = $prepare->execute() && $results = $prepare->fetchAll()) {
            return $this->entity->fillCollectionWithRelations(new Collection($results));
        }

        return new Collection();
    }

    /**
     * Prepare query from entity, fetch record and fill it with relations.
     * @return Record
     */
    public function executeOne()
    {
        $prepare = $this->repository->getConnection()->prepare($this->entity->getQuery()->limit(1));
        $prepare->setFetchMode(PDO::FETCH_CLASS, $this->entity->getRecordClass());

        if ($execute = $prepare->execute() && $record = $prepare->fetch()) {
            return $this->entity->fillRecordWithRelations($record);
        }

        return null;
    }

}