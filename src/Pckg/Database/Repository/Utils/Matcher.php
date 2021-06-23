<?php

namespace Pckg\Database\Repository\Utils;

use Pckg\Database\Record;
use Pckg\Database\Repository;

class Matcher
{

    public function matches(Record $record, array $conditions, array $binds)
    {
        $getField = function ($string) {
            $exploded = explode(' ', $string);
            $exploded = explode('.', $exploded[0]);
            $last = end($exploded);
            return trim($last, '`');
        };
        $pattern = function ($after = '') {
            return '/^`[\w`\.]*`' . $after . '$/';
        };

        foreach ($conditions as $i => $child) {
            if (!is_string($child)) {
                return false;
            }

            if (preg_match($pattern(' = \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} == $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' > \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} > $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' < \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} < $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' >= \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} >= $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' <= \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} <= $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' != \?'), $child, $matches)) {
                $field = $getField($child);
                $value = $binds[$i];

                if (!($record->{$field} != $value)) {
                    return false;
                }
            } else if (preg_match($pattern(' IN\([\\?, ]*\)'), $child, $matches)) {
                $field = $getField($child);

                if (!(in_array($record->{$field}, $binds))) {
                    return false;
                }
            } else if (preg_match($pattern(' NOT IN\([\\?, ]*\)'), $child, $matches)) {
                $field = $getField($child);

                if (in_array($record->{$field}, $binds)) {
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
