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

        return $this->processDebug($sql);
    }

    /**
     * @return array
     */
    public function buildBinds()
    {
        return $this->getBinds(['where', 'limit']);
    }

    /**
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }
}
