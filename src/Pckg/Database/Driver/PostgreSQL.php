<?php

namespace Pckg\Database\Driver;

use Pckg\Database\Repository;
use Pckg\Migration\Field;

class PostgreSQL extends PDODriver implements DriverInterface
{

    public function getShowTablesQuery(): string
    {
        return 'SELECT table_name FROM information_schema.tables WHERE table_catalog = ? AND table_name NOT LIKE \'pg%\'';
    }

    public function getTableColumns(Repository $repository, string $table): array
    {
        $prepare = $repository->getConnection()->prepare('SELECT * FROM information_schema.columns WHERE table_catalog = ? AND table_name = ?');
        $prepare->execute([$repository->getDbName(), $table]);

        $columns = [];
        foreach ($prepare->fetchAll(PDO::FETCH_ASSOC) as $field) {
            $parsedField = $this->parseColumn($field);
            $columns[$parsedField['name']] = $parsedField;
        }

        return $columns;
    }

    public function parseColumn($field)
    {
        return [
            'name' => strtolower($field['column_name']),
            'type' => strpos($field['data_type'], '(')
                ? substr($field['data_type'], 0, strpos($field['data_type'], '('))
                : $field['data_type'],
            'limit' => str_replace(
                [') unsigne'],
                '',
                substr( // @T00D00 - fix this ... example values: longblob, 7, 7 (unsigned), 8,2
                    $field['data_type'],
                    strpos($field['data_type'], '(') + 1,
                    strpos($field['data_type'], ')') ? -1 : null
                )
            ),
            'null' => $field['is_nullable'] == 'YES',
            'key' => $field['is_identity'] == 'YES'
                ? 'primary'
                : 'index',
            'default' => $field['column_default'],
            'extra' => null,
            'relations' => [],
        ];
    }

    public function getTableIndexesQuery(Repository $repository, string $table): string
    {
        return 'SELECT * FROM pg_indexes WHERE tablename = \'' . $table . '\'';
    }

    public function getIndexName(): string
    {
        return 'indexname';
    }

    public function getIndexType(array $index): string
    {
        if (isset($index[0])) {
            $index = $index[0];
        }
        $indexdef = $index['indexdef'] ?? null;
        if (!$indexdef) {
            ddd("nodef", $index);
        }
        if (strpos($indexdef, 'CREATE UNIQUE INDEX') >= 0) {
            return 'UNIQUE';
        }

        ddd("missing inp", $index);
    }

    public function getRelationsQuery(): string
    {
        return "SELECT
  c.relname as \"Name\", c.*,
  CASE c.relkind WHEN 'r' THEN 'table'
  WHEN 'v' THEN 'view'
  WHEN 'm' THEN 'materialized view'
  WHEN 'i' THEN 'index'
  WHEN 'S' THEN 'sequence'
  WHEN 's' THEN 'special'
  WHEN 'f' THEN 'foreign table' END as \"Type\"
FROM pg_catalog.pg_class c
     LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
WHERE c.relkind IN ('r','v','m','S','f','')
      AND n.nspname <> 'pg_catalog'
      AND n.nspname <> 'information_schema'
      AND n.nspname !~ '^pg_toast'
  AND pg_catalog.pg_table_is_visible(c.oid)";
    }

    public function parseRelation($result)
    {
        return [
            'table' => $result['Name'],
        ];
        $table = $result['TABLE_NAME'];
        $primary = $result['COLUMN_NAME'];
        $references = $result['REFERENCED_TABLE_NAME'];
        $on = $result['REFERENCED_COLUMN_NAME'];
        $key = 'FOREIGN__' . substr($table . '__' . $primary, -55);

        return [
            'table' => $result['Name'],
            'key' => '',
            'type' => 'FOREIGN',
            'primary' => $primary,
            'references' => $references,
            'on' => $on,
        ];
    }

    public function addFullCount()
    {
        return 'COUNT(*) OVER() AS full_count, ';
    }

    public function recapsulate($sql, $encapsulator)
    {
        if ($encapsulator === '"') {
            return $sql;
        }

        return str_replace($encapsulator, '"', $sql);
    }

    public function installField(Field $field)
    {
        if ($field instanceof Field\Id) {
            return '"' . $field->getName() . '" SERIAL';
        }

        $fieldType = $field->getType();
        if ($fieldType === 'DATETIME') {
            $field->setType('TIMESTAMP');
        } else if ($fieldType === 'LONGTEXT') {
            $field->setType('TEXT');
        } else if ($field instanceof Field\Boolean) {
            $field->setType('BOOLEAN');
        }

        $sql = [];
        $sql[] = '`' . $field->getName() . '`';
        $sql[] = $field->getType() === 'DECIMAL' ? $field->getTypeWithLength() : $field->getType();

        if (method_exists($field, 'isUnsigned') && $field->isUnsigned()) {
            $sql[] = 'UNSIGNED';
        }

        if ($field->isNullable()) {
            $sql[] = 'NULL';
        } else {
            $sql[] = 'NOT NULL';
        }

        $fieldDefault = $field->getDefault();
        if ($fieldDefault) {
            $default = '';
            if ($fieldDefault == 'CURRENT_TIMESTAMP') {
                $default = $fieldDefault;
            } else {
                $default = "'" . $fieldDefault . "'";
            }
            $sql[] = 'DEFAULT ' . $default;
        } elseif ($field->isNullable()) {
            $sql[] = 'DEFAULT NULL';
        }

        if (method_exists($field, 'isAutoIncrement') && $field->isAutoIncrement()) {
            $sql[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $sql);
    }

    public function getCreateTableQuery($table, $sql)
    {
        return 'CREATE TABLE IF NOT EXISTS "' . $table . '" (' . "\n" .
            $this->recapsulate(implode(",\n", $sql), '`') . "\n" . ')';
    }

    public function getLastInsertId(\PDO $connection, $table)
    {
        return $connection->lastInsertId($table . '_id_seq');
    }
}
