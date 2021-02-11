<?php

namespace Pckg\Database\Repository;

use Exception;
use Pckg\Cache\Cache;
use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use PDO;

class Blank implements Repository
{

    public function getConnection()
    {
        return null;
    }

    public function one(Entity $entity)
    {
        return null;
    }

    public function all(Entity $entity)
    {
        return null;
    }

    public function update(Record $record, Entity $entity)
    {
        return null;
    }

    public function delete(Record $record, Entity $entity)
    {
        return null;
    }

    public function deleteTranslation(Record $record, Entity $entity, $language)
    {
        return null;
    }

    public function insert(Record $record, Entity $entity)
    {
        return null;
    }

    public function prepareQuery(Query $query, $recordClass = null)
    {
        return null;
    }

    public function executePrepared($prepare)
    {
        return null;
    }

    public function fetchAllPrepared($prepare)
    {
        return null;
    }

    public function fetchPrepared($prepare)
    {
        return null;
    }

    public function getCache()
    {
        return null;
    }

    public function getDriver()
    {
        return null;
    }

    public function getName()
    {
        return null;
    }

}