<?php

namespace Pckg\Database\Repository\PDO\Command;

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
        /**
         * We will delete record from $table ...
         */
        $query = (new Delete())->setTable($table);

        /**
         * ... add primary key condition ...
         */
        $query->primaryWhere($this->entity, $data, $table);

        /**
         * ... prepare query ...
         */
        $prepare = $this->repository->prepareQuery($query, null);

        /**
         *  ... and execute it.
         */
        $this->repository->executePrepared($prepare);

        /**
         * @T00D00 - We should return number of deleted records or something?
         */

        return true;
    }

}