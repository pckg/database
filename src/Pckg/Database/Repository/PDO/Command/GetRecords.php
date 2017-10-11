<?php namespace Pckg\Database\Repository\PDO\Command;

use Pckg\CollectionInterface;
use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use PDO;

/**
 * Class GetAllRecords
 *
 * @package Pckg\Database\Repository\PDO\Command
 */
class GetRecords
{

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var Repository|Repository\PDO
     */
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
     *
     * @return CollectionInterface
     */
    public function executeAll()
    {
        $repository = $this->repository;
        $entity = $this->entity;

        $prepare = $repository->prepareQuery($entity->getQuery(), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $results = $repository->fetchAllPrepared($prepare)) {
            $collection = new Collection($results);
            if ($entity->getQuery()->isCounted()) {
                $prepareCount = $repository->prepareSQL('SELECT FOUND_ROWS()');
                $repository->executePrepared($prepareCount);
                $collection->setTotal($prepareCount->fetch(PDO::FETCH_COLUMN));
                $entity->count(false);
            }
            $collection->setEntity($entity)->setSaved()->setOriginalFromData();

            return $entity->fillCollectionWithRelations($collection);
        }

        return new Collection();
    }

    /**
     * Prepare query from entity, fetch record and fill it with relations.
     *
     * @return Record
     */
    public function executeOne()
    {
        $repository = $this->repository;
        $entity = $this->entity;

        $prepare = $repository->prepareQuery($entity->getQuery()->limit(1), $entity->getRecordClass());

        if ($execute = $repository->executePrepared($prepare) && $record = $repository->fetchPrepared($prepare)) {
            $record->setEntity($entity)->setSaved()->setOriginalFromData();

            return $entity->fillRecordWithRelations($record);
        }

        return null;
    }

}