<?php

namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Query\Insert;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class InsertRecord
 * @package Pckg\Database\Repository\PDO\Command
 */
class InsertRecord
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
     * @return null
     * @throws Exception
     */
    public function execute()
    {
        $data = $this->entity->tabelizeRecord($this->record);

        foreach ($data as $table => $insert) {
            if ($this->tables && !in_array($table, $this->tables)) {
                continue;
            }

            if ($this->record->{$this->entity->getPrimaryKey()}) {
                $insert[$this->entity->getPrimaryKey()] = $this->record->{$this->entity->getPrimaryKey()};
            }

            $this->record->{$this->entity->getPrimaryKey()} = $this->insert($table, $insert);
        }

        return $this->record->{$this->entity->getPrimaryKey()};
    }

    /**
     * @param       $table
     * @param array $data
     *
     * @return mixed
     * @throws Exception
     */
    public function insert($table, array $data = [])
    {
        $query = (new Insert())->table($table)->setInsert($data);
        $sql = $query->buildSQL();
        $binds = $query->buildBinds();
        $prepare = $this->repository->getConnection()->prepare($sql);

        if (!$prepare) {
            throw new Exception('Cannot prepare insert statement');
        }

        foreach ($binds as $key => $val) {
            $prepare->bindValue($key + 1, $val);
        }
        $execute = $prepare->execute();

        if (!$execute) {
            $errorInfo = $prepare->errorInfo();
            throw new Exception('Cannot execute insert statement: ' . end($errorInfo) . ' ' . $prepare->queryString);
        }

        return $this->repository->getConnection()->lastInsertId();
    }

}