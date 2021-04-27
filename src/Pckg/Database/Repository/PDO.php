<?php

namespace Pckg\Database\Repository;

use Exception;
use Pckg\Database\Driver\DriverInterface;
use Pckg\Database\Driver\MySQL;
use Pckg\Database\Driver\PostgreSQL;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO\Command\DeleteRecord;
use Pckg\Database\Repository\PDO\Command\InsertRecord;
use Pckg\Database\Repository\PDO\Command\UpdateRecord;
use Throwable;

/**
 * Class PDO
 *
 * @package Pckg\Database\Repository
 */
class PDO extends AbstractRepository implements Repository
{
    use Failable;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var null
     */
    protected $recordClass = null;

    /**
     * @var callable
     */
    protected $reconnect;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param \PDO|callable $connection
     */
    public function __construct($connection, $name = 'default')
    {
        if (is_only_callable($connection)) {
            $this->reconnect = $connection;
            $connection = $connection();
        } elseif (!($connection instanceof \PDO)) {
            throw new Exception('Connection is not a PDO connection');
        }

        $this->connection = $connection;

        $this->name = $name;
    }

    /**
     * @throws Exception
     */
    public function reconnect()
    {
        $this->connection = null;
        $this->connection = RepositoryFactory::createPdoConnectionByConfig(config('database.' . $this->name, []));
    }

    public function getDbName()
    {
        return config('database.' . $this->name . '.db', null);
    }

    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver()
    {
        $driver = config('database.' . $this->name . '.driver', null);
        if (!$driver) {
            return null;
        }

        return resolve(['mysql' => MySQL::class, 'pgsql' => PostgreSQL::class][$driver]);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            'name',
            'recordClass',
            //'reconnect',
        ];
    }

    /**
     * Reconnect on deserialization.
     */
    public function __wakeup()
    {
        $this->reconnect();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
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

    /**
     * @param Record $record
     * @param Entity $entity
     * @param        $language
     *
     * @return $this
     */
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

    /**
     * @param       $sql
     * @param array $binds
     *
     * @return mixed
     */
    public function prepareSQL($sql, $binds = [])
    {
        /**
         * Checking that the database is active prevents long-running scripts from hanging.
         */
        $prepare = $this->measureAndCheckAndReconnect(function () use ($sql) {
            return $this->getConnection()->prepare($sql);
        }, 'Preparing SQL : ' . $sql);

        if (!$prepare) {
            return $this->throwError('Cannot prepare statement', $sql);
        }

        /**
         * Bind and set fetch mode when okay.
         */
        $this->bindBinds($prepare, $binds);

        $prepare->setFetchMode(\PDO::FETCH_OBJ);

        return $prepare;
    }

    /**
     * @param $prepare \PDOStatement
     *
     * @return mixed
     * @throws Exception
     */
    public function executePrepared($prepare)
    {
        /**
         * Measure and reconnect on de-connect failure.
         * Error will still be thrown when task is not executed.
         */
        $execute = $this->measureAndCheckAndReconnect(function () use ($prepare) {
            return $prepare->execute();
        }, 'Executing ' . $prepare->queryString);

        if (!$execute) {
            return $this->throwError('Cannot execute prepared statement', implode($prepare->errorInfo()) . ' : ' . $prepare->queryString);
        }

        return $execute;
    }

    /**
     * @param       $sql
     * @param array $binds
     *
     * @return mixed
     */
    public function prepareAndExecuteSql($sql, $binds = [])
    {
        $prepare = $this->prepareSQL($sql, $binds);

        return $this->executePrepared($prepare);
    }

    /**
     * @throws Throwable
     */
    public function throwError(string $message, string $help = null)
    {
        /**
         * Add a message for dev.
         */
        if (dev() || isConsole()) {
            $message .= ' : ' . $help;
        }

        throw new Exception($message);
    }

    /**
     * @param callable $task
     * @param string $message
     * @return |null
     * @throws Throwable
     */
    private function measureAndCheckAndReconnect(callable $task, string $message)
    {
        /**
         * Start measuring.
         * Perform active check.
         * Execute task.
         * Check for failure during task - reconnect.
         */
        return measure(str_replace("\n", " ", $message), function () use ($task) {
            return $this->checkThenExecute(function () use ($task) {
                return $this->reconnectOnFailure($task);
            });
        });
    }

    /**
     * @param Query $query
     *
     * @return mixed
     */
    public function prepareExecuteAndFetchAll(Query $query)
    {
        $prepare = $this->prepareQuery($query);
        $execute = $this->executePrepared($prepare);

        return $this->fetchAllPrepared($prepare);
    }

    /**
     * @param Query $query
     * @param null $recordClass
     *
     * @return mixed
     */
    public function prepareQuery(Query $query, $recordClass = null)
    {
        $this->recordClass = $recordClass;
        $sql = $query->buildSQL();
        $binds = $query->buildBinds();

        $prepare = $this->prepareSQL($sql, $binds);

        /**
         * Trigger query.prepared event.
         */
        trigger(Query::class . '.prepared', ['sql' => $sql, 'binds' => $binds]);
        trigger(Query::class . '.preparedRepo', ['sql' => $sql, 'binds' => $binds, 'repo' => $this->getName()]);

        $prepare->setFetchMode(\PDO::FETCH_ASSOC);

        return $prepare;
    }

    /**
     * @param $prepare
     * @param $binds
     */
    protected function bindBinds($prepare, $binds)
    {
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
    }

    /**
     * @param $prepare \PDOStatement
     *
     * @return mixed
     */
    public function fetchAllPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function () use ($prepare) {
                $allFetched = $prepare->fetchAll();
                $records = $this->transformRecordsToObjects($allFetched);

                return $records;
            }
        );
    }

    /**
     * @param $prepare
     *
     * @return mixed
     */
    public function fetchPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function () use ($prepare) {
                $records = $this->transformRecordsToObjects($prepare->fetchAll());

                return $records ? $records[0] : null;
            }
        );
    }

    /**
     * @param $records
     *
     * @return mixed
     */
    public function transformRecordsToObjects($records)
    {
        if ($this->recordClass) {
            $recordClass = $this->recordClass;
            foreach ($records as &$record) {
                $record = (new $recordClass($record));
            }
        }

        return $records;
    }

    /**
     * @param Entity $entity
     * @return Record
     * @throws Exception
     */
    public function executeOne(Entity $entity)
    {
        $prepare = $this->prepareQuery($entity->getQuery()->limit(1), $entity->getRecordClass());
        if (!$prepare) {
            return $this->throwError('Cannot prepare one query');
        }

        $execute = $this->executePrepared($prepare);

        if (!$execute) {
            return $this->throwError('Cannot execute one query');
        }

        $record = $this->fetchPrepared($prepare);

        if (!$record) {
            return null;
        }

        $record->setEntity($entity)->setSaved()->setOriginalFromData();

        return $entity->fillRecordWithRelations($record);
    }

    /**
     * @param callable $callable
     * @param callable|null $onError
     * @param callable|null $onSuccess
     * @throws Throwable
     */
    public function transaction(callable $callable)
    {
        /**
         * Start DB transaction.
         */
        $this->beginTransaction();
        $return = null;

        try {
            /**
             * Run code that should be executed entirely.
             */
            $return = $callable($this);
        } catch (Throwable $e) {
            /**
             * Cancel everything on error.
             */
            message('Rollback transaction');
            $this->rollbackTransaction();
            throw $e;
            return;
        }

        /**
         * Commit everything on success.
         */
        $this->commitTransaction();
        return $return;
    }

    /**
     * @param callable $callable
     * @param null $e
     * @return false|void
     */
    public function tryTransaction(callable $callable, &$e = null)
    {
        $result = null;
        try {
            $result = $this->transaction($callable);
        } catch (Throwable $e) {
            return false;
        }

        return $result;
    }

    /**
     * @return bool
     * @throws \PDOException
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * @return bool
     * @throws \PDOException
     */
    public function rollbackTransaction()
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * @return bool
     * @throws \PDOException
     */
    public function commitTransaction()
    {
        return $this->getConnection()->commit();
    }

    /**
     * @return \Closure
     */
    public function getReconnectChecker($message)
    {
        return function () use ($message) {
            if (!$this->isReconnectMessage($message)) {
                return;
            }

            error_log('Caught reconnect - ' . $message);
            $this->reconnect();
            return true;
        };
    }

    /**
     * @param string $message
     */
    public function isReconnectMessage(string $message)
    {
        $messages = [
            'Empty server info',
            'MySQL server has gone away',
        ];
        return collect($messages)->has(function ($msg) use ($message) {
            return strpos($message, $msg) !== false;
        });
    }

    /**
     * Check for active connection, then execute the task.
     *
     * @param callable $task
     * @throws Exception
     */
    public function checkThenExecute(callable $task)
    {
        if (isConsole() && config('pckg.database.reconnect')) {
            try {
                $serverInfo = $this->getConnection()->getAttribute(\PDO::ATTR_SERVER_INFO);
                $this->getReconnectChecker($serverInfo ? $serverInfo : 'Empty server info')();
            } catch (Throwable $e) {
                if (!$this->getReconnectChecker($e->getMessage())()) {
                    throw $e;
                }
            }
        }

        return $task();
    }

    /**
     * @param callable $task
     * @return mixed
     * @throws Throwable
     */
    public function reconnectOnFailure(callable $task)
    {
        try {
            return $task();
        } catch (Throwable $e) {
            if (!$this->getReconnectChecker($e->getMessage())()) {
                throw $e;
            }
        }
    }
}
