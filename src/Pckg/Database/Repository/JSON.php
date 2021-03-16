<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class JSON
 *
 * @package Pckg\Database\Repository
 */
class JSON extends Custom
{
    use Failable;

    public function __construct($config)
    {
        if (!($config['db'] ?? null)) {
            throw new \Exception('JSON db parameter is required');
        }

        $this->config = $config;
    }

    /**
     * @param Entity $entity
     *
     * @return null
     */
    public function one(Entity $entity)
    {
        $data = $entity->all($entity);
        $query = $entity->getQuery();

        return $data->first(function (Record $record) use ($query) {
            return $this->filterRecord($record, $query);
        });
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        $db = $this->config['db'];
        $file = $entity->getTable() . '.json';
        $path = path('root') . 'static/db/json/' . $db . '/';
        if (!is_file($path . $file)) {
            return collect();
            db();die("no file");
            throw new \Exception($file . ' does not exist');
        }

        $content = json_decode(file_get_contents($path . $file), true);

        $recordClass = $entity->getRecordClass();
        $collection = collect($content)->map(function ($row) use ($recordClass) {
            return new $recordClass($row);
        });

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
         * Fill relations.
         */
        $entity->fillCollectionWithRelations($collection);

        return $collection;
    }

    public function filterRecord(Record $record, Query $query)
    {
        $where = $query->getWhere();
        if (!$where) {
            return true;
        }

        $children = $where->getChildren();
        $binds = $query->getBinds('where');

        foreach ($children as $i => $child) {
            if (!is_string($child)) {
                return false;
            }

            if (preg_match('/^`[a-z_]*` = \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} == $value)) {
                    return false;
                }
            } else if (preg_match('/^`[a-z_]*` > \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} > $value)) {
                    return false;
                }
            } else if (preg_match('/^`[a-z_]*` < \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} < $value)) {
                    return false;
                }
            } else if (preg_match('/^`[a-z_]*` >= \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} >= $value)) {
                    return false;
                }
            } else if (preg_match('/^`[a-z_]*` <= \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} <= $value)) {
                    return false;
                }
            } else if (preg_match('/^`[a-z_]*` != \?$/', $child, $matches)) {
                $field = substr($child, 1, strpos($child, '`', 1) - 1);
                $value = $binds[$i];

                if (!($record->{$field} != $value)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
