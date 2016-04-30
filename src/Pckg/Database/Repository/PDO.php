<?php

namespace Pckg\Database\Repository;

use Exception;
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

    public function prepareQuery(Query $query, $recordClass = null)
    {
        $sql = $query->buildSQL();
        $binds = $query->buildBinds();
        $prepare = $this->getConnection()->prepare($sql);

        if (!$prepare) {
            throw new Exception('Cannot prepare statement');
        }

        $i = 1;
        foreach ($binds as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $rVal) {
                    $prepare->bindValue($i, $rVal);
                    $i++;
                }
            } else {
                $prepare->bindValue($i, $val);
                $i++;
            }
        }

        if ($recordClass) {
            $prepare->setFetchMode(\PDO::FETCH_CLASS, $recordClass);
        } else {
            $prepare->setFetchMode(\PDO::FETCH_OBJ);
        }

        return $prepare;
    }

    public function executePrepared($prepare)
    {
        $execute = $prepare->execute();

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();
            throw new Exception('Cannot execute prepared statement: ' . end($errorInfo));
        }

        return $execute;
    }

    public function prepareExecuteAndFetchAll(Query $query)
    {
        $prepare = $this->prepareQuery($query);
        $execute = $this->executePrepared($prepare);

        return $this->fetchAllPrepared($prepare);
    }

}