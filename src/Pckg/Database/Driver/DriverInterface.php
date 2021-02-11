<?php namespace Pckg\Database\Driver;

interface DriverInterface
{

    public function getShowTablesQuery(): string;

    public function getTableColumnsQuery(): string;

    public function getTableIndexesQuery(): string;

    public function getIndexName(): string;

    public function getIndexType(array $index): string;

}