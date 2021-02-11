<?php

namespace Pckg\Database\Query;

use Pckg\Database\Field\Stringifiable;
use Pckg\Database\Query;

/**
 * Class Insert
 *
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
    public function buildSQL()
    {
        $sql = "INSERT INTO `" . $this->table . "` " .
               $this->buildKeys() .
               "VALUES " . $this->buildValues();

        return $this->getDriver()->recapsulate($sql, '`');
    }

    /**
     * @return string
     */
    public function buildKeys()
    {
        $arrKeys = [];

        foreach ($this->insert as $key => $val) {
            $arrKeys[] = "`" . $key . "`";
        }

        return "(" . implode(", ", $arrKeys) . ") ";
    }

    /**
     * @return string
     */
    public function buildValues()
    {
        $arrValues = [];
        foreach ($this->insert as $key => $val) {
            if (is_object($val)) {
                /**
                 * @T00D00 - invalidate raws?
                 */
                if ($val instanceof Stringifiable) {
                    /**
                     * Collect "?", "?,?" or "POINT(?, ?)
                     */
                    $arrValues[] = $val->getPlaceholder();

                    /**
                     * Bind zero or more values.
                     */
                    $this->bind($val->getBind(), 'values');
                } elseif ($val instanceof Raw) {
                    $arrValues[] = $val->buildSQL();
                    foreach ($val->getBind() as $bind) {
                        $this->bind($bind, 'values');
                    }
                } else {
                    throw new \Exception('Invalid non-stringifiable object');
                }
            } else {
                $arrValues[] = '?';
                $this->bind($val === '' ? null : $val, 'values');
            }
        }

        return "(" . implode(", ", $arrValues) . ")";
    }

    /**
     * @return array
     */
    public function buildBinds()
    {
        $binds = $this->getBinds(['keys', 'values']);

        return $binds;
    }

    /**
     * @param $insert
     *
     * @return $this
     */
    public function setInsert($insert)
    {
        $this->insert = $insert;

        return $this;
    }
}
