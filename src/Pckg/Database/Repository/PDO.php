<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO\Command\DeleteRecord;
use Pckg\Database\Repository\PDO\Command\InsertRecord;
use Pckg\Database\Repository\PDO\Command\UpdateRecord;

/**
 * Class PDO
 * @package Pckg\Database\Repository
 */
class PDO extends AbstractRepository implements Repository
{

    use Failable;

    /**
     * @var
     */
    protected $connection;

    protected $cache;

    /**
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection)
    {
        $this->setConnection($connection);
        $this->cache = new Cache($this);
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Record $record
     * @param Entity $entity
     *
     * @return $this
     */
    public function update(Record $record, Entity $entity)
    {
        (new UpdateRecord($record, $entity, $this))->execute();

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
        (new InsertRecord($record, $entity, $this))->execute();

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
        (new DeleteRecord($record, $entity, $this))->execute();

        return $this;
    }

    public function prepareQuery(Query $query, $recordClass)
    {
        try {
            $build = $query->buildSQL();
            $prepare = $this->getConnection()->prepare($build['sql']);
            $i = 1;
            foreach ($build['prepare'] as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $rVal) {
                        if (!is_numeric($key)) {
                            $tempKey = ':' . $key;
                        } else {
                            $tempKey = $i;
                        }

                        $prepare->bindValue($tempKey, $rVal);
                        if (is_numeric($key)) {
                            $i++;
                        }
                    }
                } else {
                    if (!is_numeric($key)) {
                        $tempKey = ':' . $key;
                    } else {
                        $tempKey = $i;
                    }
                    
                    $prepare->bindValue($tempKey, $val);
                    if (is_numeric($key)) {
                        $i++;
                    }
                }
            }

            $prepare->setFetchMode(\PDO::FETCH_CLASS, $recordClass);

            return $prepare;
        } catch (\Exception $e) {
            dd('pdo', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

}