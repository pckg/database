<?php namespace Pckg\Database\Entity\Extension;

trait Paginatable
{

    public function page($num) {
        $perPage = (int)$this->getQuery()->getLimit();

        if ($perPage < 1) {
            $perPage = 25;
        }

        if ($num > 1) {
            $this->limit((($num - 1) * $perPage) . ', ' . $perPage);

        } else {
            $this->limit($perPage);

        }

        return $this;
    }

}