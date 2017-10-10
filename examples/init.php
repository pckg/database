<?php

/**
 * Require composer autoloader.
 */
$autoloader = require_once 'vendor/autoload.php';
$autoloader->add('', __DIR__);

use Entity\Groups;
use Entity\Items;
use Pckg\Database\Repository\RepositoryFactory;

/**
 * Create connection with RepositoryFactory.
 * This will also bind connection as default connection.
 */
RepositoryFactory::createPdoRepository('mysql:host=localhost;charset=utf8;dbname=devdb', 'devuser', 'devpass');

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