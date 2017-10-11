<?php namespace Pckg\Database;

use Pckg\CollectionInterface;

/**
 * Interface RelationInterface
 *
 * @package Pckg\Database
 */
interface RelationInterface
{

    /**
     * @param Record $record
     *
     * @return mixed
     */
    public function fillRecord(Record $record);

    /**
     * @param Record $record
     *
     * @return mixed
     */
    public function fillRecordWithRelations(Record $record);

    /**
     * @param CollectionInterface $collection
     *
     * @return mixed
     */
    public function fillCollection(CollectionInterface $collection);

    /**
     * @param CollectionInterface $collection
     *
     * @return mixed
     */
    public function fillCollectionWithRelations(CollectionInterface $collection);

    /**
     * @param      $table
     * @param null $on
     * @param null $where
     *
     * @return mixed
     */
    public function join($table, $on = null, $where = null);

    /**
     * @param $relation
     *
     * @return mixed
     */
    public function with($relation);

}