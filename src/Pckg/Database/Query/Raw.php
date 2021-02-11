<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Raw
 *
 * @package Pckg\Database\Query
 */
class Raw extends Query
{

    /**
     * Raw constructor.
     *
     * @param null  $sql
     * @param array $bind
     */
    public function __construct($sql = null, $bind = [])
    {
        parent::__construct();
        $this->sql = $sql;
        $this->bind = $bind;
    }

    /**
     * @return null
     */
    public function buildSQL()
    {
        return $this->sql ?? $this->where->build();
    }

    /**
     * @return array
     */
    public function buildBinds()
    {
        return $this->bind;
    }
}
