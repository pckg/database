<?php namespace Pckg\Database\Query;

use Pckg\Database\Query;

class Raw extends Query
{

    public function __construct($sql = null, $bind = [])
    {
        parent::__construct();

        $this->sql = $sql;
        $this->bind = $bind;
    }

    public function buildSQL()
    {
        return $this->sql;
    }

    public function buildBinds()
    {
        return $this->bind;
    }

}