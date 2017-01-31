<?php

namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Query\Raw;
use Pckg\Database\Query\Update;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class UpdateRecord
 *
 * @package Pckg\Database\Repository\PDO\Command
 */
class UpdateRecord
{

    /**
     * @var Record
     */
    protected $record;

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @param Record     $record
     * @param Entity     $entity
     * @param Repository $repository
     */
    public function __construct(Record $record, Entity $entity, Repository $repository)
    {
        $this->record = $record;
        $this->entity = $entity;
        $this->repository = $repository;
    }

    /**
     * @param ...$tables
     *
     * @return $this
     */
    public function setTables(...$tables)
    {
        $this->tables = $tables;

        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function execute()
    {
        $originalTable = $this->entity->getTable();
        $data = $this->entity->tabelizeRecord($this->record);

        foreach ($data as $table => $update) {
            if ($this->tables && !in_array($table, $this->tables)) {
                continue;
            }

            if ($table == $this->entity->getTable()) {
                $this->update($table, $update);

            } else {
                // we don't know if children exists
                $this->updateOrInsert($table, $update);

            }
        }
        $this->entity->setTable($originalTable);

        return true;
    }

    /**
     * @param       $table
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function update($table, array $data = [])
    {
        /**
         * Flatten data for possible multivalued values like geometric POINT or so.
         */
        $cache = $this->repository->getCache();
        foreach ($data as $key => &$val) {
            if (is_array($val)) {
                if ($cache->tableHasField($table, $key) && $cache->getField($key, $table)['type'] == 'point') {
                    $val = new Raw('GeomFromText(\'POINT(' . (float)$val[0] . ' ' . (float)$val[1] . ')\')');
                }
            }
        }

        /**
         * We will update record in $table with $data ...
         */
        $query = (new Update())->setTable($table)->setSet($data);

        /**
         * ... add primary key condition ...
         */
        $query->primaryWhere($this->entity, $data, $table);

        /**
         * ... prepare query ...
         */
        $prepare = $this->repository->prepareQuery($query, null);

        /**
         * ... and execute it.
         */
        $this->repository->executePrepared($prepare);

        /**
         * Return number of updated records.
         */
        return $prepare->rowCount();
    }

    /**
     * @param       $table
     * @param array $data
     *
     * @return bool|null
     * @throws Exception
     */
    public function updateOrInsert($table, array $data = [])
    {
        $primaryKeys = $this->repository->getCache()->getTablePrimaryKeys($table);

        /**
         * @T00D00 - bug for translations, permissions ...!
         */
        if (!$primaryKeys) {
            return;
        }
        foreach ($primaryKeys as $primaryKey) {
            if (!isset($data[$primaryKey])) {
                return;
            }
        }

        $entity = (new Entity($this->repository))->setTable($table);
        foreach ($primaryKeys as $primaryKey) {
            $entity->where($primaryKey, $data[$primaryKey]);
        }

        $entity->select(['`' . $table . '`.*']);
        $record = $entity->one();

        if ($record) {
            return $this->update($table, $data);
        }

        return (new InsertRecord($this->record, $this->entity, $this->repository))
            ->setTables($table)
            ->execute();
    }

}