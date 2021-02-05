<?php

namespace Pckg\Database\Entity\Extension;

/**
 * Class Paginatable
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Paginatable
{

    /**
     * @param $num
     *
     * @return $this
     */
    public function page($num)
    {
        $perPage = (int)$this->getQuery()->getLimit();

        if ($perPage < 1) {
            $perPage = 50;
        }

        if ($num > 1) {
            $this->limit((($num - 1) * $perPage) . ', ' . $perPage);
        } else {
            $this->limit($perPage);
        }

        return $this;
    }
}
