<?php

namespace Pckg\Database;

use PDO;

interface ConnectableRepository
{
    /**
     * @return PDO
     */
    public function getConnection();
}
