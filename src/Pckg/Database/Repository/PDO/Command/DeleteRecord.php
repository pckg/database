<?php

namespace Pckg\Database\Repository\PDO\Command;

use Pckg\Database\Entity;
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

    }

}