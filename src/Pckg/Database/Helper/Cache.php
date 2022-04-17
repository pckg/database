<?php

namespace Pckg\Database\Helper;

use Exception;
use Pckg\Cache\Cache as PckgCache;
use Pckg\Database\ConnectableRepository;
use Pckg\Database\Repository;
use PDO;

/**
 * Class Cache
 *
 * @package Pckg\Database\Helper
 *          Provides simple cache for database fields and relations.
 */
class Cache extends PckgCache
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @var bool
     */
    protected $built = false;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->readFromCache();

        if (!$this->built) {
            $this->rebuild();
        }
    }

    public function rebuild()
    {
        $this->buildCache();
        $this->writeToCache();
    }

    /**
     *
     */
    protected function buildCache()
    {
        $this->buildTables();
        $this->buildRelations();

        parent::buildCache();
    }

    /**
     *
     */
    protected function buildTables()
    {
        $connection = $this->repository->getConnection();
        if (!$connection) {
            return;
        }
        $sql = $this->repository->getDriver()->getShowTablesQuery();
        $prepare = $connection->prepare($sql);
        $prepare->execute([$this->repository->getDbName()]);

        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $table) {
            $table = end($table);
            $table = strtolower($table);
            $this->buildFields($table);
            $this->buildConstraints($table);
            $this->buildPrimaryKeys($table);
        }
    }

    /**
     * @param $table
     */
    protected function buildFields($table)
    {
        $this->cache['tables'][$table] = [];
        $this->cache['fields'][$table] = [];

        $driver = $this->repository->getDriver();

        $this->cache['fields'][$table] = $driver->getTableColumns($this->repository, $table);
    }

    /**
     * @param $table
     */
    protected function buildConstraints($table)
    {
        $driver = $this->repository->getDriver();

        $this->cache['constraints'][$table] = $driver->getTableConstraints($this->repository, $table);
    }

    /**
     * @param $table
     */
    protected function buildPrimaryKeys($table)
    {
        $this->cache['tables'][$table]['primaryKeys'] = array_column(
            array_filter(
                $this->cache['fields'][$table],
                function ($field) {
                    return $field['key'] == 'primary';
                }
            ),
            'name'
        );
    }

    /**
     *
     */
    protected function buildRelations()
    {
        $connection = $this->repository->getConnection();
        if (!$connection) {
            return;
        }
        $driver = $this->repository->getDriver();
        $prepare = $connection->prepare($driver->getRelationsQuery());
        $prepare->execute();
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $parsedRelation = $driver->parseRelation($result);
            continue;
            $this->cache['constraints'][$parsedRelation['table']][$parsedRelation['key']] = $parsedRelation;
        }
    }

    /**
     * @param $table
     *
     * @return array
     */
    public function getTableFields($table)
    {
        if (!$table) {
            throw new Exception('Table should be set!');
        }

        return array_keys($this->cache['fields'][$table] ?? []);
    }

    /**
     * @param $field
     * @param $table
     *
     * @return mixed
     */
    public function getField($field, $table)
    {
        return $this->getTable($table)['fields'][$field];
    }

    /**
     * @param $table
     *
     * @return array
     */
    public function getTable($table)
    {
        return array_merge(
            $this->cache['tables'][$table],
            [
                'fields'      => $this->cache['fields'][$table] ?? [],
                'constraints' => $this->cache['constraints'][$table] ?? [],
            ]
        );
    }

    /**
     * @param $field
     * @param $table
     *
     * @return mixed
     */
    public function getConstraint($constraint, $table)
    {
        return $this->getTable($table)['constraints'][$constraint] ?? [];
    }

    /**
     * @param $table
     * @param $field
     *
     * @return bool
     */
    public function tableHasField($table, $field)
    {
        return isset($this->cache['fields'][$table]) && array_key_exists($field, $this->cache['fields'][$table]);
    }

    public function getExtendeeTableForField($table, $field)
    {

        foreach ($this->cache['fields'] ?? [] as $tbl => $fields) {
            if (strpos($tbl, $table) === 0 && strlen($table) + 5 == strlen($tbl)) {
                if ($this->tableHasField($tbl, $field)) {
                    return $tbl;
                }
            }
        }

        return null;
    }

    /**
     * @param $table
     * @param $field
     *
     * @return bool
     */
    public function tableHasConstraint($table, $constraint)
    {
        return isset($this->cache['constraints'][$table]) &&
               array_key_exists($constraint, $this->cache['constraints'][$table]);
    }

    public function getTableConstraints($table)
    {
        return $this->cache['constraints'][$table] ?? [];
    }

    /**
     * @param $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        return isset($this->cache['tables'][$table]);
    }

    /**
     * @param $table
     *
     * @return mixed
     */
    public function getTablePrimaryKeys($table)
    {
        return $this->cache['tables'][$table]['primaryKeys'] ?? [];
    }

    /**
     * @return string
     */
    protected function getCachePath()
    {
        return static::getCachePathByRepository($this->repository);
    }

    /**
     * @param Repository $repository
     *
     * @return string
     */
    public static function getCachePathByRepository(Repository $repository)
    {
        $part = $repository instanceof ConnectableRepository
            ? ($repository->getConnection()->uniqueName ?? '')
            : '';

        $path = path('cache') . 'framework/pckg_database_repository_'
            . sluggify(get_class(app()))
            . '_'
            . sluggify(get_class(env()))
            . '_' . $repository->getName() . '_' . $part . '.cache';

        return $path;
    }
}
