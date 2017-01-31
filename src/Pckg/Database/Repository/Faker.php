<?php namespace Pckg\Database\Repository;

use Faker\Generator;
use Pckg\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use Pckg\Database\Repository\Faker\Fetcher;

class Faker extends AbstractRepository implements Repository
{

    /**
     * @param \PDO $connection
     */
    public function __construct(Generator $connection)
    {
        $this->setConnection($connection);
        $this->cache = new Cache($this);
    }

    public function update(Record $record, Entity $entity)
    {
        return $this;
    }

    public function delete(Record $record, Entity $entity)
    {
        return $this;
    }

    public function insert(Record $record, Entity $entity)
    {
        return $this;
    }

    public function prepareQuery(Query $query, $recordClass = null)
    {
        return new Fetcher($this, $query, $recordClass);
    }

    public function getCache()
    {
        // TODO: Implement getCache() method.
    }

    public function deleteTranslation(Record $record, Entity $entity, $language)
    {
        // TODO: Implement deleteTranslation() method.
    }
}