<?php

namespace Pckg\Database\Driver;

use Pckg\Database\Repository;

abstract class PDODriver
{

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