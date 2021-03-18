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

    protected $localCache = [];

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
            return $query ? $this->filterRecord($record, $query) : $record;
        });
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        $collection = $this->getCachedFile($entity);

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
        $getField = function ($string) {
            $exploded = explode(' ', $string);
            $exploded = explode('.', $exploded[0]);
            $last = end($exploded);
            return trim($last, '`');
        };

        foreach ($children as $i => $child) {
            if (!is_string($child)) {
                return false;
            }

            if (preg_match('/^`[\w`\.]*` = \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} == $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` > \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} > $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` < \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} < $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` >= \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} >= $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` <= \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} <= $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` != \?$/', $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} != $value)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` IN\([\\?, ]*\)$/', $child, $matches)) {
                $field = $getField($child);

                if (!(in_array($record->{$field}, $binds))) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` NOT IN\([\\?, ]*\)$/', $child, $matches)) {
                $field = $getField($child);

                if (in_array($record->{$field}, $binds)) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` IS NULL$/', $child, $matches)) {
                $field = $getField($child);

                if (!(is_null($record->{$field}))) {
                    return false;
                }
            } else if (preg_match('/^`[\w`\.]*` IS NOT NULL$/', $child, $matches)) {
                $field = $getField($child);

                if (is_null($record->{$field})) {
                    return false;
                }
            } else {
                ddd($child, $binds, $query);
                throw new \Exception('Unsupported custom repository operation');
                return false;
            }
        }

        return true;
    }

    public function getCachedFile(Entity $entity)
    {
        $db = $this->config['db'];
        $file = $entity->getTable() . '.json';
        $path = path('root') . 'static/db/json/' . $db . '/';
        $sub = null;
        $cache = sha1($file . $path);

        /**
         * Simple request cache.
         */
        if (isset($this->localCache[$cache])) {
            return $this->localCache[$cache];
        }

        /**
         * Check for permissions.
         */
        if (!is_file($path . $file) && strpos(strrev($file), strrev('_p17n.json')) === 0) {
            $file = substr($file, 0, -strlen('_p17n.json')) . '.json';
            $sub = 'allPermissions';
        }

        /**
         * Return empty collection on error.
         */
        if (!is_file($path . $file)) {
            error_log('Database file ' . $file . ' does not exist');
            return $this->localCache[$cache] = collect();
        }

        /**
         * Read collection from static files.
         */
        $content = json_decode(file_get_contents($path . $file), true);
        $rows = collect($content);

        /**
         * Create sub-collections.
         */
        $recordClass = $entity->getRecordClass();
        $rows = $rows->map(function ($row) use ($recordClass) {
            if (isset($row['allPermissions'])) {
                $row['allPermissions'] = collect($row['allPermissions'])->map(function ($permission) use ($recordClass) {
                    return new $recordClass($permission);
                });
            }
            return $row;
        });

        /**
         * Map to colleciton of Records.
         */
        return $this->localCache[$cache] = $sub
            ? ($rows->map(function (array $row) use ($recordClass, $sub) {
                return collect($row[$sub])->map(function ($realRow) use ($recordClass) {
                    return new $recordClass($realRow);
                });
            })->flat())
            : ($rows->map(function (array $row) use ($recordClass) {
                return new $recordClass($row);
            }));
    }
}
