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
 *
 * @package Pckg\Database\Repository
 */
class PDO extends AbstractRepository implements Repository
{

    use Failable;

    /**
     * @var
     */
    protected $connection;

    protected $name;

    protected $recordClass = null;

    public function __sleep()
    {
        return [
            'name',
            'recordClass',
        ];
    }

    public function __wakeup()
    {
        $repository = RepositoryFactory::createRepositoryConnection(config('database.' . $this->name), $this->name);
        $this->connection = $repository->getConnection();
    }

    /**
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection, $name = 'default')
    {
        $this->setConnection($connection);
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        $key = 'pckg.database.repository.cache.' . sha1(Cache::getCachePathByRepository($this));
        $context = context();

        if (!$context->exists($key)) {
            $context->bind($key, $cache = new Cache($this));
        }

        return $context->get($key);
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

    public function deleteTranslation(Record $record, Entity $entity, $language)
    {
        (new DeleteRecord($record, $entity, $this))->setTable($entity->getTable() . '_i18n')
                                                   ->setData(
                                                       [
                                                           $entity->getTable() . '_i18n' => [
                                                               'language_id' => $language,
                                                           ],
                                                       ]
                                                   )->execute();

        return $this;
    }

    public function prepareSQL($sql, $binds = [])
    {
        $prepare = measure(
            'Prepare query: ' . $sql,
            function() use ($sql, $binds) {
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

                $prepare->setFetchMode(\PDO::FETCH_OBJ);

                return $prepare;
            }
        );

        return $prepare;
    }

    public function prepareQuery(Query $query, $recordClass = null)
    {
        $this->recordClass = $recordClass;
        $sql = $query->buildSQL();
        $prepare = measure(
            'Prepare query: ' . $sql,
            function() use ($query, $sql) {
                $binds = $query->buildBinds();
                $prepare = $this->getConnection()->prepare($sql);

                if (!$prepare) {
                    throw new Exception('Cannot prepare statement');
                }

                /**
                 * Trigger query.prepared event.
                 */
                trigger(Query::class . '.prepared', ['sql' => $sql, 'binds' => $binds]);

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

                $prepare->setFetchMode(\PDO::FETCH_ASSOC);

                return $prepare;
            }
        );

        return $prepare;
    }

    public function prepareAndExecuteSql($sql, $binds = [])
    {
        $prepare = $this->prepareSQL($sql, $binds);

        return $this->executePrepared($prepare);
    }

    /**
     * @param $prepare \PDOStatement
     *
     * @return mixed
     * @throws Exception
     */
    public function executePrepared($prepare)
    {
        $execute = measure(
            'Execute query: ' . str_replace("\n", " ", $prepare->queryString),
            function() use ($prepare) {
                return $prepare->execute();
            }
        );

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();

            throw new Exception(
                'Cannot execute prepared statement: ' . end($errorInfo) . ' : ' . $prepare->queryString
            );
        }

        return $execute;
    }

    public function prepareExecuteAndFetchAll(Query $query)
    {
        $prepare = $this->prepareQuery($query);
        $execute = $this->executePrepared($prepare);

        return $this->fetchAllPrepared($prepare);
    }

    public function fetchAllPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function() use ($prepare) {
                $records = $this->transformRecordsToObjects($prepare->fetchAll());
                //$prepare->setFetchMode(\PDO::FETCH_CLASS, $this->recordClass);
                //$records = $prepare->fetchAll();

                return $records;
            }
        );
    }

    public function fetchPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function() use ($prepare) {
                $records = $this->transformRecordsToObjects($prepare->fetchAll());
                //$prepare->setFetchMode(\PDO::FETCH_CLASS, $this->recordClass);
                //$records = $prepare->fetchAll();

                return $records ? $records[0] : null;
            }
        );
    }

    public function transformRecordsToObjects($records)
    {
        if ($this->recordClass) {
            $recordClass = $this->recordClass;
            foreach ($records as &$record) {
                $record = (new $recordClass($record))/*->setData($record)*/
                ;
            }
        }

        return $records;
    }

}