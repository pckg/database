<?php namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Field\Stringifiable;
use Pckg\Database\Query\Insert;
use Pckg\Database\Query\Raw;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class InsertRecord
 *
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
        $this->repository = $repository->aliased('write');
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
        $data = $this->entity->tabelizeRecord($this->record, false, false);
        foreach ($data as $table => $insert) {
            if ($this->tables && !in_array($table, $this->tables)) {
                continue;
            }

            if ($this->record->{$this->entity->getPrimaryKey()}) {
                /**
                 * Primary key is already set, we need to update it.
                 */
                $insert[$this->entity->getPrimaryKey()] = $this->record->{$this->entity->getPrimaryKey()};
                $this->insert($table, $insert);
            } else {
                /**
                 * Primary key is not set yet, we need to set it now.
                 */
                $this->record->{$this->entity->getPrimaryKey()} = $this->insert($table, $insert);
                $this->record->setSaved();
            }
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
        /**
         * We will insert $data into $table ...
         */
        $query = (new Insert())->table($table)->setInsert($data);

        /**
         * ... prepare query ...
         */
        $prepare = $this->repository->prepareQuery($query);

        /**
         * ... execute it ...
         */
        $this->repository->executePrepared($prepare);

        /**
         * ... and return inserted ID.
         */
        return $this->repository->getConnection()->lastInsertId();
    }

}