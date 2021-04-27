<?php

namespace Pckg\Database\Repository\PDO\Command;

use Exception;
use Pckg\Database\Entity;
use Pckg\Database\Query\Delete;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class DeleteRecord
 *
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
     * @var null
     */
    protected $table = null;

    /**
     * @var array
     */
    protected $data = [];

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
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     *
     */
    public function execute()
    {
        $data = $this->entity->tabelizeRecord($this->record, false, false);

        /**
         * We need to get entity table extensions.
         * Lets hardcode them for now.
         */
        $extensions = ['i18n', 'p17n', 'l11e', ''];
        $table = $this->table ?? $this->entity->getTable();
        $primaryKeys = $this->entity->getRepository()->getCache()->getTablePrimaryKeys($table);

        if (!$primaryKeys) {
            $primaryKeys = ['id'];
            //throw new Exception('Will NOT delete from table without primary keys ...');
        }

        foreach ($extensions as $ext) {
            if ($ext) {
                $ext = '_' . $ext;
            }
            if ($this->entity->getRepository()->getCache()->hasTable($table . $ext)) {
                /**
                 * We will delete record from $table ...
                 */
                $query = (new Delete())->setDriver($this->repository->getDriver())->setTable($table . $ext);

                /**
                 * ... add primary key condition ...
                 */
                $mergedData = array_merge($data[$table] ?? [], $this->data[$table] ?? []);
                foreach ($primaryKeys as $key) {
                    $query->where($key, $mergedData[$key]);
                }

                /**
                 * ... prepare query ...
                 */
                $prepare = $this->repository->prepareQuery($query);

                /**
                 *  ... and execute it.
                 */
                try {
                    $this->repository->executePrepared($prepare);
                } catch (\Throwable $e) {
                    ddd(exception($e));
                }
            }
        }

        $this->record->setSaved(false);
        $this->record->setDeleted(true);

        return true;
    }
}
