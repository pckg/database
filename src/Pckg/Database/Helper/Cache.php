<?php

namespace Pckg\Database\Helper;

use Exception;
use Pckg\Database\Repository;
use Pckg\Framework\Cache as FrameworkCache;
use PDO;

/**
 * Class Cache
 *
 * @package Pckg\Database\Helper
 *          Provides simple cache for database fields and relations.
 */
class Cache extends FrameworkCache
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
            $this->buildCache();
            $this->writeToCache();
        }
    }

    protected function buildCache()
    {
        $this->buildTables();
        $this->buildRelations();

        parent::buildCache();
    }

    protected function getCachePath()
    {
        return static::getCachePathByRepository($this->repository);
    }

    public static function getCachePathByRepository(Repository $repository)
    {
        return path('cache') . 'framework/pckg_database_repository_' . str_replace(
            ['\\', '/'],
            '_',
            (get_class(app()) . '_' . get_class(env()))
        ) . '_' . $repository->getName() . '_' . ($repository->getConnection()->uniqueName ?? '') . '.cache';
    }

    /**
     *
     */
    protected function buildTables()
    {
        $sql = 'SHOW TABLES';
        $prepare = $this->repository->getConnection()->prepare($sql);
        $prepare->execute();

        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $table) {
            $table = end($table);
            $table = strtolower($table);
            $this->buildFields($table);
            $this->buildConstraints($table);
            $this->buildPrimaryKeys($table);
        }
    }

    protected function buildConstraints($table)
    {
        $this->cache['constraints'][$table] = [];

        $sql = 'SHOW INDEX IN `' . $table . '`';
        $prepare = $this->repository->getConnection()->prepare($sql);
        $prepare->execute();

        $indexes = [];

        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $constraint) {
            $indexes[$constraint['Key_name']][] = $constraint;
        }

        foreach ($indexes as $indexs) {
            $first = $indexs[0];
            $name = $first['Key_name'];

            if ($name == 'PRIMARY') {
                $this->cache['constraints'][$table][$name] = [
                    'type' => 'PRIMARY',
                ];
            } else {
                if (strpos($name, 'FOREIGN__') === 0) {
                    /**
                     * This is handled in buildRelations();
                     */
                    $this->cache['constraints'][$table][$first['Key_name']] = [
                        'type' => 'FOREIGN',
                    ];
                } else if ($first['Non_unique']) {
                    $this->cache['constraints'][$table][$first['Key_name']] = [
                        'type' => 'KEY',
                    ];
                } else {
                    $this->cache['constraints'][$table][$first['Key_name']] = [
                        'type' => 'UNIQUE',
                    ];
                }
            }
        }
    }

    /**
     * @param $table
     */
    protected function buildFields($table)
    {
        $this->cache['tables'][$table] = [];
        $this->cache['fields'][$table] = [];
        $sql = 'SHOW FULL COLUMNS IN ';
        $prepare = $this->repository->getConnection()->prepare($sql . '`' . $table . '`');
        $prepare->execute();
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $field) {
            $field['Field'] = strtolower($field['Field']);
            $this->cache['fields'][$table][$field['Field']] = [
                'name'      => $field['Field'],
                'type'      => strpos($field['Type'], '(')
                    ? substr($field['Type'], 0, strpos($field['Type'], '('))
                    : $field['Type'],
                'limit'     => str_replace([') unsigne'], '', substr( // @T00D00 - fix this ... example values: longblob, 7, 7 (unsigned), 8,2
                    $field['Type'],
                    strpos($field['Type'], '(') + 1,
                    strpos($field['Type'], ')') ? -1 : null
                )),
                'null'      => $field['Null'] == 'YES',
                'key'       => $field['Key'] == 'PRI'
                    ? 'primary'
                    : $field['Key'],
                'default'   => $field['Default'],
                'extra'     => $field['Extra'],
                'relations' => [],
            ];
        }
    }

    /**
     * @param $table
     */
    protected function buildPrimaryKeys($table)
    {
        $this->cache['tables'][$table]['primaryKeys'] = array_column(
            array_filter(
                $this->cache['fields'][$table],
                function($field) {
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
        $sql = 'SELECT `TABLE_SCHEMA`, `TABLE_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_SCHEMA`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`
  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = SCHEMA() AND `REFERENCED_TABLE_NAME` IS NOT NULL;';
        $prepare = $this->repository->getConnection()->prepare($sql);
        $prepare->execute();
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $table = $result['TABLE_NAME'];
            $primary = $result['COLUMN_NAME'];
            $references = $result['REFERENCED_TABLE_NAME'];
            $on = $result['REFERENCED_COLUMN_NAME'];
            $key = 'FOREIGN__' . substr($table . '__' . $primary, -55);

            $this->cache['constraints'][$table][$key] = [
                'type'       => 'FOREIGN',
                'primary'    => $primary,
                'references' => $references,
                'on'         => $on,
            ];
        }
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
                'fields'      => $this->cache['fields'][$table],
                'constraints' => $this->cache['constraints'][$table],
            ]
        );
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

        return array_keys($this->cache['fields'][$table]);
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
     * @param $field
     * @param $table
     *
     * @return mixed
     */
    public function getConstraint($constraint, $table)
    {
        return $this->getTable($table)['constraints'][$constraint];
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

    /**
     * @param $table
     * @param $field
     *
     * @return bool
     */
    public function tableHasConstraint($table, $constraint)
    {
        return isset($this->cache['constraints'][$table]) && array_key_exists(
            $constraint,
            $this->cache['constraints'][$table]
        );
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
        return $this->cache['tables'][$table]['primaryKeys'];
    }

}