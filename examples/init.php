<?php

/**
 * Require composer autoloader.
 */
$autoloader = require_once 'vendor/autoload.php';
$autoloader->add('', __DIR__);

use Entity\Groups;
use Entity\Items;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO as RepositoryPDO;

/**
 * Create pure PDO connection.
 */
$pdoConnection = new PDO('mysql:host=localhost;charset=utf8;dbname=devdb', 'devuser', 'devpass');

/**
 * Create PDO database repository.
 */
$connection = new RepositoryPDO($pdoConnection);

/**
 * Bind connection to context.
 */
context()->bind(Repository::class, $connection);
context()->bind(Repository::class . '.default', $connection);

/**
 * Select all groups, with items in one separate query.
 * $groups will contain Collection of Group records.
 * Each group will have items relation with Collection of Item records.
 */
$groups = (new Groups())->withItems()->all();

/**
 * Select all items, with corresponding group in one separate query.
 * $items will contain Colleciton of Item records.
 * Each item will have group relation with Group record.
 */
$items = (new Items())->withGroup()->all();