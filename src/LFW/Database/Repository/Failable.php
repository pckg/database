<?php

namespace LFW\Database\Repository;

use Exception;
use LFW\Database\Collection;
use LFW\Database\Record;

/**
 * Class Failable
 * @package LFW\Database\Repository
 */
trait Failable
{

    /**
     * @return Record
     * @throws Exception
     */
    public function oneOrFail()
    {
        if ($result = $this->one()) {
            return $result;
        }

        throw new Exception('No record found');
    }

    /**
     * @throws Exception
     * @return Collection
     * */
    public function allOrFail()
    {
        if ($results = $this->all()) {
            return $results;
        }

        throw new Exception('No records found');
    }

}