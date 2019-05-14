<?php namespace Pckg\Database\Repository;

use Exception;
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
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection, $name = 'default')
    {
        $this->setConnection($connection);
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            'name',
            'recordClass',
        ];
    }

    /**
     *
     */
    public function __wakeup()
    {
        $repository = RepositoryFactory::createRepositoryConnection(config('database.' . $this->name), $this->name);
        $this->connection = $repository->getConnection();
    }

    /**
     * @return string
     */
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
    public function prepareAndExecuteSql($sql, $binds = [])
    {
        $prepare = $this->prepareSQL($sql, $binds);

        return $this->executePrepared($prepare);
    }

    /**
     * @param       $sql
     * @param array $binds
     *
     * @return mixed
     */
    public function prepareSQL($sql, $binds = [])
    {
        $prepare = $this->getConnection()->prepare($sql);

        if (!$prepare) {
            throw new Exception('Cannot prepare statement');
        }

        $this->bindBinds($prepare, $binds);

        $prepare->setFetchMode(\PDO::FETCH_OBJ);

        return $prepare;
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param $prepare \PDOStatement
     *
     * @return mixed
     * @throws Exception
     */
    public function executePrepared($prepare)
    {
        $execute = $prepare->execute();

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();

            throw new Exception(
                'Cannot execute prepared statement: ' . end($errorInfo) . ' : ' . $prepare->queryString
            );
        }

        return $execute;
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
     * @param null  $recordClass
     *
     * @return mixed
     */
    public function prepareQuery(Query $query, $recordClass = null)
    {
        $this->recordClass = $recordClass;
        $sql = $query->buildSQL();
        $binds = $query->buildBinds();
        $prepare = $this->getConnection()->prepare($sql);

        if (!$prepare) {
            if (dev()) {
                throw new Exception('Cannot prepare statement: ' . implode(", ", $this->getConnection()->errorInfo()) . ' : ' . $sql);
            }

            throw new Exception('Cannot prepare statement');
        }

        /**
         * Trigger query.prepared event.
         */
        trigger(Query::class . '.prepared', ['sql' => $sql, 'binds' => $binds]);
        trigger(Query::class . '.preparedRepo', ['sql' => $sql, 'binds' => $binds, 'repo' => $this->getName()]);

        $this->bindBinds($prepare, $binds);

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
            function() use ($prepare) {
                $allFetched = $prepare->fetchAll();
                $records = $this->transformRecordsToObjects($allFetched);

                return $records;
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
     * @param $prepare
     *
     * @return mixed
     */
    public function fetchPrepared($prepare)
    {
        return measure(
            'Fetching prepared',
            function() use ($prepare) {
                $records = $this->transformRecordsToObjects($prepare->fetchAll());

                return $records ? $records[0] : null;
            }
        );
    }
    
    public function executeOne(Entity $entity) {
        $prepare = $this->prepareQuery($entity->getQuery()->limit(1), $entity->getRecordClass());

        $measure = str_replace("\n", " ", $prepare->queryString);
        startMeasure('Executing ' . $measure);
        if ($execute = $this->executePrepared($prepare) && $record = $this->fetchPrepared($prepare)) {
            $record->setEntity($entity)->setSaved()->setOriginalFromData();

            stopMeasure('Executing ' . $measure);

            return $entity->fillRecordWithRelations($record);
        }
        stopMeasure('Executing ' . $measure);
    }

    public function transaction(callable $callable){
        /**
         * Start DB transaction.
         */
        $this->beginTransaction();
        $return = null;

        try {
            /**
             * Run code that should be executed entirely.
             */
            $return = $callable();
        } catch (Throwable $e) {
            /**
             * Cancel everything on error.
             */
            $this->rollbackTransaction();
            throw $e;
        } finally {
            /**
             * Commit everything on success.
             */
            $this->commitTransaction();
            return $return;
        }
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function rollbackTransaction()
    {
        return $this->connection->rollBack();
    }

    public function commitTransaction()
    {
        return $this->connection->commit();
    }

}