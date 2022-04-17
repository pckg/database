<?php

namespace Pckg\Database\Repository;

use Exception;
use Pckg\Collection;
use Pckg\Database\Record;

/**
 * Class Failable
 *
 * @package Pckg\Database\Repository
 */
trait Failable
{
    /**
     * @return Record
     * @throws Exception
     */
    public function oneOrFail()
    {
        /*if ($result = $this->one()) {
            return $result;
        }*/

        throw new Exception('No record found');
    }

    /**
     * @throws Exception
     * @return Collection
     * */
    public function allOrFail()
    {
        /*if ($results = $this->all()) {
            return $results;
        }*/

        throw new Exception('No records ' . substr(static::class, strrpos(static::class, '\\') + 1) . ' found');
    }
}
