<?php

namespace Pckg\Database\Driver;

use Pckg\Database\Repository;

interface DriverInterface
{

    public function getShowTablesQuery(): string;

    public function getTableColumns(Repository $repository, string $table): array;

    public function getTableIndexesQuery(Repository $repository, string $table): string;

    public function getIndexName(): string;

    public function getIndexType(array $index): string;
}
