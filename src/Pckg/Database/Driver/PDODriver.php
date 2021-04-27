<?php

namespace Pckg\Database\Driver;

use Pckg\Database\Helper\Cache;
use Pckg\Database\Repository;
use Pckg\Migration\Field;
use Pckg\Migration\Table;

/**
 * Class PDODriver
 * @package Pckg\Database\Driver
 * @extends
 * @see MySQL
 * @see PostgreSQL
 */
abstract class PDODriver
{

    abstract public function getTableIndexesQuery(Repository $repository, string $table): string;

    abstract public function getIndexName(): string;

    abstract public function getIndexType(array $index): string;

    public function getTableConstraints(Repository $repository, string $table)
    {
        $connection = $repository->getConnection();
        $prepare = $connection->prepare($this->getTableIndexesQuery($repository, $table));
        $prepare->execute();

        $indexes = [];
        $indexName = $this->getIndexName();
        foreach ($prepare->fetchAll(\PDO::FETCH_ASSOC) as $constraint) {
            $indexes[$constraint[$indexName]][] = $constraint;
        }

        $constraints = [];
        foreach ($indexes as $indexs) {
            $first = $indexs[0];
            $name = $first[$indexName];

            $type = $this->getIndexType($first);
            $constraints[$name] = [
                'type' => $type,
            ];
        }

        return $constraints;
    }

    public function updateField(Cache $cache, Table $table, Field $field)
    {
        $newSql = $this->installField($field);
        $oldSql = str_replace(['CHARACTER VARYING', ' WITHOUT TIME ZONE', 'INTEGER'], ['VARCHAR', '', 'INT'], $this->buildOldFieldSql($cache, $table, $field));

        if ($newSql != $oldSql) {
            d($newSql, $oldSql);
            return $newSql;
        }
    }

    /**
     * @param Cache $cache
     * @param Table $table
     * @param Field $field
     *
     * @return string
     */
    protected function buildOldFieldSql(Cache $cache, Table $table, Field $field)
    {
        $encapsulator = $this->getEncapsulator();
        $cachedField = $cache->getField($field->getName(), $table->getName());

        if (strpos($cachedField['default'], 'nextval(') === 0) {
            return $encapsulator . $cachedField['name'] . $encapsulator . ' SERIAL';
        }

        return '`' . $cachedField['name'] . '` '
            . strtoupper($cachedField['type'])
            . ($cachedField['limit'] ? '(' . $cachedField['limit'] . ')' : '')
            . ($cachedField['null'] ? ' NULL' : ' NOT NULL')
            . ($cachedField['default']
                ? ' DEFAULT '
                . ($cachedField['default'] == 'CURRENT_TIMESTAMP'
                    ? $cachedField['default']
                    : (strpos($cachedField['default'], 'NULL') === 0
                        ? 'NULL'
                        : ("'" . $cachedField['default'] . "'")))
                : ($cachedField['null']
                    ? ' DEFAULT NULL'
                    : ''))
            . ($cachedField['extra'] ? ' ' . strtoupper($cachedField['extra']) : '');
    }
}
