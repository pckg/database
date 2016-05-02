<?php

namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Query\Delete;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class DeleteRecord
 * @package Pckg\Database\Repository\PDO\Command
 */
class DeleteRecord
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
     *
     */
    public function execute()
    {
        $data = $this->entity->tabelizeRecord($this->record);

        foreach ($data as $table => $data) {
            $this->delete($table, $data);
        }

        return true;
    }

    public function delete($table, array $data = [])
    {
        $query = (new Delete())->setTable($table);

        foreach ($this->entity->getRepository()->getCache()->getTablePrimaryKeys($table) as $primaryKey) {
            $query->where($primaryKey, $data[$primaryKey]);
        }

        $sql = $query->buildSQL();
        $binds = $query->buildBinds();
        $prepare = $this->repository->getConnection()->prepare($sql);

        if (!$prepare) {
            throw new Exception('Cannot prepare delete statement');
        }

        foreach ($binds as $key => $val) {
            $prepare->bindValue($key + 1, $val);
        }

        $execute = $prepare->execute();

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();
            throw new Exception('Cannot execute delete statement: ' . end($errorInfo));
        }

        return true;
    }

}