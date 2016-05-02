<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Delete
 * @package Pckg\Database\Query
 */
class Delete extends Query
{
    /**
     * @var
     */
    protected $insert;

    // builders
    /**
     * @return string
     */
    function buildSQL()
    {
        return "DELETE FROM `" . $this->table . "` " .
        ($this->where ? $this->buildWhere() : '') .
        ($this->limit ? 'LIMIT ' . $this->limit : '');
    }

    function buildBinds()
    {
        return $this->getBinds(['where', 'limit']);
    }

    /**
     * @param $table
     * @return $this
     */
    function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

}