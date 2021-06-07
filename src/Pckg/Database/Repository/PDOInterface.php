<?php

namespace Pckg\Database\Repository;

use Pckg\Database\Driver\DriverInterface;

interface PDOInterface
{

    /**
     * @return DriverInterface
     */
    public function getDriver();
}
