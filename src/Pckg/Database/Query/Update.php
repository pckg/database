<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Update
 * @package Pckg\Database\Query
 */
class Update extends Query
{
    /**
     * @var
     */
    protected $set;

    // builders
    /**
     * @return string
     */
    function buildSQL()
    {
        $sql = "UPDATE `" . $this->table . "` " .
        "SET " . $this->buildSet() . " " .
        $this->buildWhere() .
        ($this->limit ? ' LIMIT ' . $this->limit : '');

        return $sql;
    }

    function buildBinds()
    {
        return $this->getBinds(['set', 'where', 'limit']);
    }

    // builders

    /**
     * @return string
     */
    function buildSet()
    {
        $arrValues = [];

        foreach ($this->set AS $key => $val) {
            $keyPart = "`" . $key . "` = ";

            if (is_bool($val)) {
                $val = $val ? 1 : null;
            } else if (empty($val)) {
                $val = null;
            }

            $arrValues[] = $keyPart . '?';
            $this->bind['set'][] = $val;
        }

        return implode(", ", $arrValues);
    }

    // setters
    /**
     * @param $set
     * @return $this
     */
    function setSet($set)
    {
        $this->set = $set;

        return $this;
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

    /**
     * @param $where
     * @return $this
     */
    function setWhere($where)
    {
        $this->where = $where;

        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    // adders
    /**
     * @param $where
     * @return $this
     */
    function addWhere($where)
    {
        $this->where[] = $where;

        return $this;
    }
}