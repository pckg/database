<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Delete
 *
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
    public function buildSQL()
    {
        $sql = "DELETE FROM `" . $this->table . "` " .
            ($this->where ? $this->buildWhere() : '');

        $parts = [$sql];
        if ($this->orderBy) {
            $parts[] = 'ORDER BY ' . ($this->orderBy == 'id' ? '`' . $this->table . "`.`" . $this->orderBy .
                    '` ASC' : $this->orderBy);
        }

        if ($this->limit) {
            $parts[] = 'LIMIT ' . $this->limit;
        }

        $sql = implode(' ', $parts);
        
        return $this->getDriver()->recapsulate($sql, '`');
    }

    /**
     * @return array
     */
    public function buildBinds()
    {
        return $this->getBinds(['where', 'limit']);
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }
}
