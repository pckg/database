<?php

namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Query\Update;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class UpdateRecord
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

        return true;
    }

    /**
     * @param       $table
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update($table, array $data = [])
    {
        $query = (new Update())->setTable($table)->setSet($data);

        foreach ($this->entity->getRepository()->getCache()->getTablePrimaryKeys($table) as $primaryKey) {
            $query->where($primaryKey, $data[$primaryKey]);
        }

        $sql = $query->buildSQL();
        $binds = $query->buildBinds();
        $prepare = $this->repository->getConnection()->prepare($sql);

        if (!$prepare) {
            throw new Exception('Cannot prepare update statement');
        }

        foreach ($binds as $key => $val) {
            $prepare->bindValue($key + 1, $val);
        }

        $execute = $prepare->execute();

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();
            throw new Exception('Cannot execute update statement: ' . end($errorInfo));
        }

        return true;
    }

    /**
     * @param       $table
     * @param array $data
     * @return bool|null
     * @throws Exception
     */
    public function updateOrInsert($table, array $data = [])
    {
        $primaryKeys = $this->repository->getCache()->getTablePrimaryKeys($table);
        foreach ($primaryKeys as $primaryKey) {
            $this->entity->where($primaryKey, $data[$primaryKey]);
        }
        $record = $this->entity->setTable($table)->one();

        if ($record) {
            return $this->update($table, $data);
        }

        return (new InsertRecord($this->record, $this->entity, $this->repository))
            ->setTables($table)
            ->execute();
    }

}