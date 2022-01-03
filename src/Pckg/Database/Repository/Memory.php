<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Collection;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;
use Pckg\Database\Repository\Utils\Matcher;

/**
 * Class JSON
 *
 * @package Pckg\Database\Repository
 */
class Memory extends Custom
{
    use Failable;

    protected array $collection = [];

    /**
     * @param Entity $entity
     *
     * @return null
     */
    public function one(Entity $entity)
    {
        $data = $entity->all();
        $query = $entity->getQuery();

        return $data->first(function (Record $record) use ($query) {
            return $this->filterRecord($record, $query);
        });
    }

    public function getCollection(Entity $entity): \Pckg\Collection
    {
        return collect($this->collection[$entity->getTable()] ?? [])
            ->map(fn($data) => $entity->getRecord($data));
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        $collection = $this->getCollection($entity);

        /**
         * Immediately return empty collection.
         */
        if (!$collection->count()) {
            return $collection;
        }

        /**
         * Return collection when there's no query to apply.
         */
        $query = $entity->getQuery();
        if (!$query) {
            return $collection;
        }

        /**
         * Filter.
         */
        $where = $query->getWhere();
        if ($where && $where->hasChildren()) {
            $collection = $collection->filter(function (Record $record) use ($query) {
                return $this->filterRecord($record, $query);
            });
        }

        /**
         * Sort? Limit?
         */
        $sort = $query->getOrderBy();
        if ($sort) {
            $collection = $collection->sortBy($sort);
        }

        /**
         * Fill relations.
         */
        $entity->fillCollectionWithRelations($collection);

        return $collection;
    }

    public function filterRecord(Record $record, Query $query = null)
    {
        if (!$query) {
            return true;
        }

        $where = $query->getWhere();
        if (!$where) {
            return true;
        }

        $children = $where->getChildren();
        $binds = $query->getBinds('where');

        return (new Matcher())->matches($record, $children, $binds);
    }
}
