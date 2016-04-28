<?php

namespace Pckg\Database\Query;

use Pckg\Database\Query;

/**
 * Class Insert
 * @package Pckg\Database\Query
 */
class Insert extends Query
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
        return "INSERT INTO `" . $this->table . "` " .
        $this->buildKeys() .
        "VALUES " . $this->buildValues();
    }

    public function buildBinds()
    {
        return $this->getBinds(['keys', 'values']);
    }

    /**
     * @return string
     */
    function buildKeys()
    {
        $arrKeys = [];

        foreach ($this->insert AS $key => $val) {
            $arrKeys[] = "`" . $key . "`";
        }

        return "(" . implode(", ", $arrKeys) . ") ";
    }

    /**
     * @return string
     */
    function buildValues()
    {
        $arrValues = [];
        foreach ($this->insert AS $key => $val) {
            $arrValues[] = '?';
            $this->bind($val, 'values');
        }

        return "(" . implode(", ", $arrValues) . ") ";
    }

    /**
     * @param $insert
     * @return $this
     */
    function setInsert($insert)
    {
        $this->insert = $insert;

        return $this;
    }
}