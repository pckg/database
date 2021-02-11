<?php

namespace Pckg\Database\Driver;

use Pckg\Migration\Field;

class MySQL implements DriverInterface
{

    public function getShowTablesQuery(): string
    {
        return 'SHOW TABLES';
    }

    public function getTableColumnsQuery(): string
    {
        return 'SHOW FULL COLUMNS IN `' . $table . '`';
    }

    public function getTableIndexesQuery(): string
    {
        return 'SHOW INDEX IN `' . $table . '`';
    }

    public function getIndexName(): string
    {
        return 'Key_name';
    }

    public function getIndexType(array $index): string
    {
        $name = $index[$this->getIndexName()];

        if ($name == 'PRIMARY') {
            return 'PRIMARY';
        } else {
            if (strpos($name, 'FOREIGN__') === 0) {
                return 'FOREIGN';
            } else if ($first['Non_unique']) {
                return 'KEY';
            } else {
                return 'UNIQUE';
            }
        }
    }

    public function getRelationsQuery(): string
    {
        return 'SELECT `TABLE_SCHEMA`, `TABLE_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_SCHEMA`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`
  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = SCHEMA() AND `REFERENCED_TABLE_NAME` IS NOT NULL;';
    }

    public function parseColumn($field)
    {
        $field['Field'] = strtolower($field['Field']);
        return [
            'name' => $field['Field'],
            'type' => strpos($field['Type'], '(')
                ? substr($field['Type'], 0, strpos($field['Type'], '('))
                : $field['Type'],
            'limit' => str_replace([') unsigne'], '',
                substr( // @T00D00 - fix this ... example values: longblob, 7, 7 (unsigned), 8,2
                    $field['Type'],
                    strpos($field['Type'], '(') + 1,
                    strpos($field['Type'], ')') ? -1 : null
                )),
            'null' => $field['Null'] == 'YES',
            'key' => $field['Key'] == 'PRI'
                ? 'primary'
                : $field['Key'],
            'default' => $field['Default'],
            'extra' => $field['Extra'],
            'relations' => [],
        ];
    }

    public function parseRelation($result)
    {
        $table = $result['TABLE_NAME'];
        $primary = $result['COLUMN_NAME'];
        $references = $result['REFERENCED_TABLE_NAME'];
        $on = $result['REFERENCED_COLUMN_NAME'];
        $key = 'FOREIGN__' . substr($table . '__' . $primary, -55);

        return [
            'table' => $table,
            'key' => $key,
            'type' => 'FOREIGN',
            'primary' => $primary,
            'references' => $references,
            'on' => $on,
        ];
    }

    public function addFullCount()
    {
        return 'SQL_CALC_FOUND_ROWS ';
    }

    public function recapsulate($sql, $encapsulator)
    {
        if ($encapsulator === '`') {
            return $sql;
        }

        return str_replace($encapsulator, '`', $sql);
    }

    public function installField(Field $field)
    {
        $sql = [];
        $sql[] = '`' . $field->getName() . '`';
        $sql[] = $field->getTypeWithLength();

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
        return 'CREATE TABLE IF NOT EXISTS `' . $table . '` (' . "\n" .
            implode(",\n", $sql) . "\n" . ') ENGINE=InnoDB DEFAULT CHARSET=utf8';
    }

    public function getLastInsertId(\PDO $connection, $table)
    {
        return $connection->lastInsertId();
    }

}