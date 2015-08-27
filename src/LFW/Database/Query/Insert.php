<?php

namespace LFW\Database\Query;

use LFW\Database\Query;

/**
 * Class Insert
 * @package LFW\Database\Query
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
        $sql = "INSERT INTO `" . $this->table . "` " .
            $this->buildKeys() .
            "VALUES " . $this->buildValues();
        return $sql;
    }

    /**
     * @param $insert
     * @return mixed
     */
    function setInsert($insert)
    {
        $this->insert = $insert;

        return $this;
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
            $arrVal = "";
            if (is_bool($val)) {
                $arrVal .= $val ? 1 : 0;
            } else if (empty($val)) {
                $arrVal .= "NULL";
            } else {
                $arrVal .= static::escape($val);
            }

            $arrValues[] = $arrVal;
        }

        return "(" . implode(", ", $arrValues) . ") ";
    }
}