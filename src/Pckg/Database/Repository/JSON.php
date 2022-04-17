<?php

namespace Pckg\Database\Repository;

use Pckg\Collection;
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
class JSON extends Memory
{
    protected $localCache = [];

    /**
     * @var array|mixed
     */
    protected $config = [];

    public function __construct($config)
    {
        if (!($config['db'] ?? null)) {
            throw new \Exception('JSON db parameter is required');
        }

        $this->config = $config;
    }

    public function getCollection(Entity $entity): Collection
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
        $rows = $rows->map(function ($row) {
            if (isset($row['allPermissions'])) {
                $row['allPermissions'] = collect($row['allPermissions'])->map(function ($permission) {
                    return new Record($permission);
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
