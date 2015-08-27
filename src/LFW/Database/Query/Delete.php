<?php

namespace LFW\Database\Query;

use LFW\Database\Query;

/**
 * Class Delete
 * @package LFW\Database\Query
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
}