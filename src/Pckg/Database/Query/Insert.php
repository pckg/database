<?php namespace Pckg\Database\Query;

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
    function buildSQL()
    {
        $sql = "INSERT INTO `" . $this->table . "` " .
               $this->buildKeys() .
               "VALUES " . $this->buildValues();

        return $sql;
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
    function setInsert($insert)
    {
        $this->insert = $insert;

        return $this;
    }
}