<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Database\Collection;
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
     * @param Entity $entity
     */
    public function __construct(Entity $entity, Repository $repository)
    {
        $this->entity = $entity;
        $this->repository = $repository;
    }

    /**
     * @return Collection
     */
    public function executeAll()
    {
        $prepare = new PrepareSQL($this->entity->getQuery(), $this->entity->getRepository());
        $prepare = $prepare->execute();

        if ($prepare && $execute = $prepare->execute() && $results = $prepare->fetchAll(PDO::FETCH_CLASS, $this->entity->getRecordClass())) {
            return $this->fillCollection($results);
        }

        return new Collection();
    }

    /**
     * @return Record
     */
    public function executeOne()
    {
        $prepare = $this->repository->getConnection()->prepare($this->entity->getQuery()->limit(1));
        $prepare->setFetchMode(PDO::FETCH_CLASS, $this->entity->getRecordClass());

        if ($prepare->execute() && $record = $prepare->fetch()) {
            return $this->fillRecord($record);
        }

        return null;
    }

    /**
     * @param $results
     * @return array|Collection
     */
    protected function fillCollection(array $results)
    {
        $collection = new Collection($results);

        foreach ($this->entity->getWith() as $relation) {
            $relation->fillCollection($collection);
        }

        return $collection;
    }

    protected function fillRecord(Record $record)
    {
        foreach ($this->entity->getWith() as $relation) {
            $relation->fillRecord($record);
        }

        return $record;
    }

}