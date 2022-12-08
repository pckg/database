<?php

namespace Pckg\Database\Repository;

use Faker\Generator;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use Pckg\Database\Repository\Faker\Fetcher;

/**
 * Class Faker
 *
 * @package Pckg\Database\Repository
 */
class Faker extends AbstractRepository implements Repository
{
    public function __construct(Generator $connection)
    {
        $this->setConnection($connection);
        $this->cache = new Cache($this);
    }

    public function getName()
    {
        return 'faker';
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return $this
     */
    public function update(Record $record, Entity $entity)
    {
        return $this;
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return $this
     */
    public function delete(Record $record, Entity $entity)
    {
        return $this;
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return $this
     */
    public function insert(Record $record, Entity $entity)
    {
        return $this;
    }

    /**
     * @param Query $query
     * @param null  $recordClass
     *
     * @return Fetcher
     */
    public function prepareQuery(Query $query, $recordClass = null)
    {
        return new Fetcher($this, $query, $recordClass);
    }

    /**
     *
     */
    public function getCache()
    {
        // TODO: Implement getCache() method.
    }

    /**
     * @param Record $record
     * @param Entity $entity
     * @param        $language
     */
    public function deleteTranslation(Record $record, Entity $entity, $language)
    {
        throw new \Exception('Faker::deleteTranslation not implemented');
        return $record;
    }

    public function getDriver()
    {
        // TODO: Implement getDriver() method.
    }
}
