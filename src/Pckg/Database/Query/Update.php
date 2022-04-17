<?php

namespace Pckg\Database\Query;

use Pckg\Database\Field\Stringifiable;
use Pckg\Database\Query;

/**
 * Class Update
 *
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
    public function buildSQL()
    {
        $sql = "UPDATE `" . $this->table . "` " .
               "SET " . $this->buildSet() . " " .
               $this->buildWhere() .
               ($this->limit ? ' LIMIT ' . $this->limit : '');

        return $this->processDebug($sql);
    }

    /**
     * @return string
     */
    public function buildSet()
    {
        $arrValues = [];

        foreach ($this->set as $key => $val) {
            $keyPart = "`" . $key . "` = ";

            /**
             * Booleans are transformed to 1 OR NULL.
             */
            if (is_bool($val)) {
                $arrValues[] = $keyPart . $this->getDriver()->makeBool($val);
                continue;
            }

            /**
             * Empty values are transformed to NULL.
             */
            if (empty($val)) {
                $arrValues[] = $keyPart . 'NULL';
                continue;
            }

            /**
             * Scalar values are binded.
             */
            if (is_scalar($val)) {
                $arrValues[] = $keyPart . '?';
                $this->bind($val, 'set');
                continue;
            }

            /**
             * Objects are validated for stringification.
             */
            if (is_object($val)) {
                /**
                 * @T00D00 - invalidate raws?
                 */
                if ($val instanceof Stringifiable) {
                    /**
                     * Collect "?", "?,?" or "POINT(?, ?)
                     */
                    $arrValues[] = $keyPart . $val->getPlaceholder();

                    /**
                     * Bind zero or more values.
                     */
                    $this->bind($val->getBind(), 'set');
                    continue;
                }

                if ($val instanceof Raw) {
                    $arrValues[] = $keyPart . $val->buildSQL();
                    foreach ($val->getBind() as $bind) {
                        $this->bind($bind, 'set');
                    }
                    continue;
                }

                throw new \Exception('Cannot use object as a SQL value in key ' . $key);
            }

            throw new \Exception('Not scalar value in key ' . $key);
        }

        return implode(", ", $arrValues);
    }

    // builders

    /**
     * @return array
     */
    public function buildBinds()
    {
        return $this->getBinds(['set', 'where', 'limit']);
    }

    // setters

    /**
     * @param $set
     *
     * @return $this
     */
    public function setSet($set)
    {
        $this->set = $set;

        return $this;
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

    /**
     * @param $where
     *
     * @return $this
     */
    public function setWhere($where)
    {
        $this->where = $where;

        return $this;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    // adders
    /**
     * @param $where
     *
     * @return $this
     */
    public function addWhere($where)
    {
        $this->where[] = $where;

        return $this;
    }
}
