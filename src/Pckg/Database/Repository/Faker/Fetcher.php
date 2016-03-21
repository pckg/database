<?php namespace Pckg\Database\Repository\Faker;

use Faker\Generator;
use Pckg\Collection;
use Pckg\Database\Query;
use Pckg\Database\Repository\Faker;

class Fetcher
{

    protected $faker;

    protected $query;

    protected $recordClass;

    public function __construct(Faker $faker, Query $query, $recordClass)
    {
        $this->faker = $faker;
        $this->query = $query;
        $this->recordClass = $recordClass;
    }

    public function execute()
    {
        return $this;
    }

    /**
     * @return Generator
     */
    public function getGenerator()
    {
        return $this->faker->getConnection();
    }

    public function fetchAll()
    {
        $limit = (int)$this->query->getLimit();
        if (!$limit) {
            $limit = 20;
        }

        $collection = [];
        for ($i = 1; $i <= $limit; $i++) {
            $collection[] = $this->fetch($i);
        }

        return $collection;
    }

    public function fetch($i = 1)
    {
        $generator = $this->getGenerator();
        $cache = $this->faker->getCache();
        $table = $this->query->getTable();
        $fields = $cache->getTable($table);
        $record = new $this->recordClass;
        foreach ([$table, $table . '_i18n'] as $t) {
            $fields = $cache->getTable($t);
            foreach ($fields['fields'] as $field => $meta) {
                try {
                    $record->{$field} = $generator->{$field};
                    continue;
                } catch (\Exception $e) {

                }
                if ($meta['name'] == 'id') {
                    $record->{$field} = $i;

                } elseif ($meta['name'] == 'parent_id') {
                    $parentId = $i % 10 - 1;
                    $record->{$field} = $parentId > 0 ? $parentId : null;

                } elseif (substr($meta['name'], 0, 3) == 'dt_') {
                    if (strpos($meta['name'], 'added', 3)) {
                        $record->{$field} = date('Y-m-d H:i:s',
                            strtotime(round(rand(0, 100000) - rand(0, 10000)) . ' seconds'));

                    } elseif (strpos($meta['name'], 'updated', 3)) {
                        $record->{$field} = date('Y-m-d H:i:s', strtotime(-round(rand(0, 100000)) . ' seconds'));

                    } elseif (strpos($meta['name'], 'deleted', 3)) {
                        $record->{$field} = rand(0, 1) > 0.9 || true
                            ? date('Y-m-d H:i:s', strtotime(-round(rand(0, 100000)) . ' seconds'))
                            : null;
                        $record->{$field} = date('Y-m-d H:i:s', strtotime(-round(rand(0, 100000)) . ' seconds'));

                    } else {
                        dd($meta);
                    }

                } elseif ($meta['name'] == 'language_id') {
                    $record->{$field} = 'en';

                } elseif ($meta['name'] == 'title') {
                    $record->{$field} = $generator->sentence(rand(3, 9));

                } elseif ($meta['name'] == 'subtitle') {
                    $record->{$field} = implode(' ', $generator->sentences(rand(1, 4)));

                } elseif ($meta['name'] == 'description') {
                    $record->{$field} = '<p>' . implode('</p><p>', $generator->paragraphs(rand(2, 6))) . '</p>';

                } elseif ($meta['name'] == 'content') {
                    $record->{$field} = '<p>' . implode('</p><p>', $generator->paragraphs(rand(5, 15))) . '</p>';

                } else {
                    try {
                        $record->{$field} = $generator->{$field};
                        continue;
                    } catch (\Exception $e) {
                        dd($meta);
                    }
                }
            }
        }

        return $record;
    }

}