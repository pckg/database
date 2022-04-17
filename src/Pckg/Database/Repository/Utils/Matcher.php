<?php

namespace Pckg\Database\Repository\Utils;

use Pckg\Database\Query\Parenthesis;
use Pckg\Database\Record;
use Pckg\Database\Repository;

class Matcher
{
    public function matches(Record $record, array $conditions, array $binds, int &$numBinds = 0)
    {
        $getField = function ($string) {
            $exploded = explode(' ', $string);
            $exploded = explode('.', $exploded[0]);
            $last = end($exploded);
            return trim($last, '`');
        };
        $pattern = function ($after = '') {
            return '/^[`]?[\w`\._]*[`]?' . $after . '$/';
        };

        foreach ($conditions as $i => $child) {
            if (is_object($child) && $child instanceof Parenthesis) {
                $subMatch = $this->matches($record, $child->getChildren(), $binds, $numBinds);
                if (!$subMatch) {
                    return false;
                }
                continue;
            }

            if (!is_string($child)) {
                throw new \Exception('Should be string');
                return false;
            }

            $matches = [];
            if (preg_match($pattern(' = \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if ($record->{$field} != $value) {
                    return false;
                }
            } else if (preg_match($pattern(' != \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if ($record->{$field} == $value) {
                    return false;
                }
            } else if (preg_match($pattern(' > \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if (!($record->{$field} > $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' < \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if (!($record->{$field} < $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' >= \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if (!($record->{$field} >= $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' <= \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$numBinds++];

                if (!($record->{$field} <= $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' IN\([\\?, ]*\)'), $child, $matches)) {
                $field = $getField($child);
                $tempBinds = array_slice($binds, $numBinds, count(explode('?', $child)) - 1);
                $numBinds += count($tempBinds);

                if (!(in_array($record->{$field}, $tempBinds))) {
                    return false;
                }
            } else if (preg_match($pattern(' NOT IN\([\\?, ]*\)'), $child, $matches)) {
                $field = $getField($child);
                $tempBinds = array_slice($binds, $numBinds, count(explode('?', $child)) - 1);
                $numBinds += count($tempBinds);

                if (in_array($record->{$field}, $tempBinds)) {
                    return false;
                }
            } else if (preg_match($pattern(' IS NULL'), $child, $matches)) {
                $field = $getField($child);

                if (!(is_null($record->{$field}))) {
                    return false;
                }
            } else if (preg_match($pattern(' IS NOT NULL'), $child, $matches)) {
                $field = $getField($child);

                if (is_null($record->{$field})) {
                    return false;
                }
            } else if (preg_match($pattern(), $child, $matches)) {
                $field = $getField($child);

                if (!$record->{$field}) {
                    return false;
                }
            } else {
                throw new \Exception('Unsupported custom repository operation');
                return false;
            }
        }

        return true;
    }
}
