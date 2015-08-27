<?php

namespace Pckg\Database\Helper;

use Pckg\Database\Repository;
use PDO;

/**
 * Class Cache
 * @package Pckg\Database\Helper
 * Provides simple cache for database fields and relations.
 */
class Cache
{

    protected $repository;

    protected $cache = [];

    protected $fields = [];

    protected $tables = [];

    protected $built = false;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->readFromCache();

        if (!$this->built) {
            $this->buildTables();
            $this->buildRelations();
            $this->writeToCache();
        }
    }

    private function readFromCache()
    {
        $file = path('cache') . 'framework/database_' . str_replace(['\\', '/'], '_', (get_class(app()) . '_' . get_class(env()))) . '.cache';
        if (file_exists($file)) {
            $cache = json_decode(file_get_contents($file), true);
            $this->fields = $cache['fields'];
            $this->tables = $cache['tables'];
            $this->built = true;
        }
    }

    private function writeToCache()
    {
        $file = path('cache') . 'framework/database_' . str_replace(['\\', '/'], '_', (get_class(app()) . '_' . get_class(env()))) . '.cache';
        $cache = [];
        $cache['fields'] = $this->fields;
        $cache['tables'] = $this->tables;
        $cache = json_encode($cache);
        file_put_contents($file, $cache);
    }

    protected function buildTables()
    {
        $sql = 'SHOW TABLES';
        $prepare = $this->repository->getConnection()->prepare($sql);
        $prepare->execute();

        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $table) {
            $table = end($table);
            $this->buildFields($table);
            $this->buildPrimaryKeys($table);
        }
    }

    protected function buildFields($table)
    {
        $this->tables[$table] = [];
        $this->fields[$table] = [];
        $sql = 'SHOW FULL COLUMNS IN ';
        $prepare = $this->repository->getConnection()->prepare($sql . '`' . $table . '`');
        $prepare->execute();
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $field) {
            $this->fields[$table][$field['Field']] = [
                'name' => $field['Field'],
                'type' => substr($field['Type'], 0, strpos($field['Type'], '(')),
                'limit' => substr($field['Type'], strpos($field['Type'], '(') + 1, strpos($field['Type'], ')') ? -1 : null),
                'null' => $field['Null'] == 'NO',
                'key' => $field['Key'] == 'PRI'
                    ? 'primary'
                    : $field['Key'],
                'default' => $field['Default'],
                'extra' => $field['Extra'],
                'relations' => [],
            ];
        }
    }

    protected function buildPrimaryKeys($table)
    {
        $this->tables[$table]['primaryKeys'] = array_column(array_filter($this->fields[$table], function ($field) {
            return $field['key'] == 'primary';
        }), 'name');
    }

    protected function buildRelations()
    {
        $sql = 'SELECT `TABLE_SCHEMA`, `TABLE_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_SCHEMA`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`
  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = SCHEMA() AND `REFERENCED_TABLE_NAME` IS NOT NULL;';
        $prepare = $this->repository->getConnection()->prepare($sql);
        $prepare->execute();
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $result) {
            var_dump($result);
        }
    }

    public function getTable($table)
    {
        return array_merge($this->tables[$table], ['fields' => $this->fields[$table]]);
    }

    public function getTableFields($table)
    {
        return array_keys($this->fields[$table]);
    }

    public function getField($field, $table)
    {
        return $this->getTable($table)[$field];
    }

    public function tableHasField($table, $field)
    {
        return array_key_exists($field, $this->fields[$table]);
    }

    public function getTablePrimaryKeys($table)
    {
        return $this->tables[$table]['primaryKeys'];
    }

}