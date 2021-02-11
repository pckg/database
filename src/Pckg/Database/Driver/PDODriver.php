<?php

namespace Pckg\Database\Driver;

use Pckg\Database\Repository;

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
}
