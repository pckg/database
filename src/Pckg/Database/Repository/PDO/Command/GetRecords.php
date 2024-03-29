<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\CollectionInterface;
use Pckg\Database\Collection;
use Pckg\Database\Driver\MySQL;
use Pckg\Database\Driver\PostgreSQL;
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
        $this->repository = ($repository
                             ?? $this->entity->getRepository())->aliased('read');
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
        $query = $entity->getQuery();

        $isCounted = $query->isCounted();
        $queryDriver = $query->getDriver();
        if ($isCounted && (get_class($queryDriver) !== MySQL::class)) {
            $query->addSelect(['total_count' => 'COUNT(*) OVER()']);
        }

        $prepare = $repository->prepareQuery($query, $entity->getRecordClass());

        $measure = str_replace("\n", " ", $prepare->queryString);
        $hash = sha1($measure . microtime());

        startMeasure('Executing ' . $measure);
        $execute = $repository->executePrepared($prepare);
        stopMeasure('Executing ' . $measure);

        if (!$execute) {
            return new Collection();
        }

        $results = $repository->fetchAllPrepared($prepare);

        if (!$results) {
            return new Collection();
        }

        $collection = new Collection($results);
        if ($isCounted) {
            if (get_class($queryDriver) === MySQL::class) {
                startMeasure('Counting ' . $hash);
                $prepareCount = $repository->prepareSQL('SELECT FOUND_ROWS()');
                $repository->executePrepared($prepareCount);
                $collection->setTotal($prepareCount->fetch(PDO::FETCH_COLUMN));
                $entity->count(false);
                stopMeasure('Counting ' . $hash);
            } else {
                $collection->setTotal($results[0]->total_count);
            }
        }

        startMeasure('Setting original ' . $hash);
        $collection->setEntity($entity)->setSaved()->setOriginalFromData();
        stopMeasure('Setting original ' . $hash);

        startMeasure('Filling relations ' . $hash);
        $filled = $entity->fillCollectionWithRelations($collection);
        stopMeasure('Filling relations ' . $hash);

        return $filled;
    }

    /**
     * Prepare query from entity, fetch record and fill it with relations.
     *
     * @return Record
     */
    public function executeOne()
    {
        return $this->repository->executeOne($this->entity);
    }
}
