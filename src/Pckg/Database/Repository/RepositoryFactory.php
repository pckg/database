<?php

namespace Pckg\Database\Repository;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar;
use Exception;
use Faker\Factory;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO as RepositoryPDO;
use PDO;
use PDOException;
use Pckg\Database\Connection\PDO as PDOConnection;

/**
 * Class RepositoryFactory
 *
 * @package Pckg\Database\Repository
 */
class RepositoryFactory
{
    /**
     *
     */
    const DEFAULT_NAME = 'default';

    /**
     * @var array
     */
    protected static $repositories = [];

    const DRIVER_FAKER = 'faker';
    const DRIVER_MIDDLEWARE = 'middleware';
    const DRIVER_JSON = 'json';
    const DRIVER_DYNAMODB = 'dynamodb';
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_PGSQL = 'pgsql';

    /**
     * @return mixed|null|\Pckg\Database\Repository\PDO
     * @throws Exception
     */
    public static function getOrCreateRepository($name)
    {
        $name = $name == Repository::class
            ? 'default'
            : str_replace(Repository::class . '.', '', $name);

        $repository = null;
        if (array_key_exists($name, static::$repositories)) {
            $repository = static::$repositories[$name];
        } else {
            $fullName = Repository::class . '.' . $name;
            if (!context()->exists($fullName)) {
                /**
                 * Lazy load.
                 */
                $config = config('database.' . $name);
                /**
                 * @T00D00 - this means that we're overloading every non-default repository to default one?
                 *         - this is needed when mixing different and using multiple repositories
                 *         - maybe the best thing would be to change repositories when needed (leave defaults?)
                 */
                if (!$config && $name != 'default') {
                    if (class_exists($name) && object_implements($name, Repository::class)) {
                        // we cannot auto-resolve because we need a specific instane
                        // $resolved = resolve($name);
                        return new $name();
                    }
                    $config = config('database.default');
                } else if (is_string($config) && config('database.' . $config)) {
                    // dynamic -> default
                    $config = config('database.' . $config);
                }
                $repository = RepositoryFactory::getRepositoryByConfig($config, $name);
                context()->bind($fullName, $repository);
            }

            $repository = context()->get($fullName);
        }

        if (!$repository) {
            throw new Exception('Cannot prepare repository');
        }

        return $repository;
    }

    public static function canCreateRepository($name)
    {
        $name = $name === Repository::class
            ? 'default'
            : str_replace(Repository::class . '.', '', $name);

        if (array_key_exists($name, static::$repositories)) {
            return true;
        }

        $fullName = Repository::class . '.' . $name;
        if (context()->exists($fullName)) {
            return true;
        }

        $config = config('database.' . $name);
        if ($config) {
            return true;
        }

        if (class_exists($name) && object_implements($name, Repository::class)) {
            return true;
        }

        return false;
    }

    /**
     * @return \Pckg\Database\Repository\PDO
     * @throws Exception
     */
    public static function initPdoDatabase($config, $name)
    {
        return new RepositoryPDO((function () use ($config, $name) {
            try {
                $pdo = static::createPdoConnectionByConfig($config);
            } catch (PDOException $e) {
                throw new Exception('Cannon instantiate database connection: ' . $e->getMessage());
            };

            static::checkDebugBar($pdo, $name);

            return $pdo;
        }), $name);
    }

    /**
     * @return PDO
     */
    public static function createPdoConnectionByConfig($config)
    {
        /**
         * Merge configs.
         */
        $options = ($config['options'] ?? []) + [
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

        /**
         * Backwards compatible timezone set.
         */
        if (isset($config['timezone'])) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET time_zone = \'' . $config['timezone'] . '\';';
        }

        /**
         * Charser, timezone and database selection.
         */
        $timezone = config('pckg.locale.timezone', 'Europe/Ljubljana');
        $charset = $config['charset'] ?? 'utf8';
        $partDb = isset($config['db'])
            ? ";dbname=" . $config['db']
            : '';

        /**
         * Connect by socket or host.
         */
        $to = 'host';
        $key = 'host';
        $scheme = $config['driver'] ?? static::DRIVER_MYSQL;
        if (isset($config['socket'])) {
            $to = 'unix_socket';
            $key = 'socket';
        }

        $finalCharset = ";charset=" . $charset;
        $finalOptions = '';
        if ($scheme === static::DRIVER_PGSQL) {
            $finalCharset = '';
            $finalOptions = ';options=\'--client_encoding=' . $charset . '\'';
        }

        $dsn = $scheme . ":" . $to . "=" . $config[$key] . $finalCharset . $partDb . $finalOptions;
        $pdo = new PDOConnection(
            $dsn,
            $config['user'],
            $config['pass'],
            $options
        );

        $pdo->uniqueName = $config['host'] . "-" . $config['db'];

        return $pdo;
    }

    protected static function checkDebugBar($pdo, $name)
    {
        if (!context()->exists(DebugBar::class)) {
            return;
        }

        $debugBar = context()->find(DebugBar::class);
        $tracablePdo = new TraceablePDO($pdo);

        if ($debugBar->hasCollector('pdo')) {
            $pdoCollector = $debugBar->getCollector('pdo');
        } else {
            $debugBar->addCollector($pdoCollector = new PDOCollector());
        }

        $pdoCollector->addConnection($tracablePdo, str_replace(':', '-', $name));
    }

    /**
     * @return Faker|\Pckg\Database\Repository\PDO
     */
    public static function createRepositoryConnection($config, $name)
    {
        /**
         * Create faker, middleware or pdo repository.
         */
        $repository = static::getRepositoryByConfig($config, $name);

        /**
         * Bind repository to context so we can reuse it later.
         */
        if ($pos = strpos($name, ':')) {
            /**
             * We're probably initializing write connection.
             */
            $originalAlias = Repository::class . '.' . substr($name, 0, $pos);
            $originalRepository = context()->get($originalAlias);
            $alias = substr($name, $pos + 1);
            $originalRepository->addAlias($alias, $repository);
            $repository->addAlias($alias == 'write' ? 'read' : 'write', $originalRepository);
        } else {
            context()->bindIfNot(Repository::class, $repository);
        }

        context()->bind(Repository::class . '.' . $name, $repository);

        return $repository;
    }

    /**
     * Instantiate connection for defined driver.
     *
     * @return Faker|RepositoryPDO
     * @throws Exception
     */
    protected static function getRepositoryByConfig($config, $name)
    {
        if (!is_array($config)) {
            /**
             * Custom (or not :)) static class repository.
             */
            if (class_exists($config)) {
                return new $config();
            }

            /**
             * Could be aliased?
             */
            throw new Exception('Cannot create repository from string');
        } elseif ($config['driver'] === static::DRIVER_FAKER) {
            return new Faker(Factory::create());
        } elseif ($config['driver'] === static::DRIVER_MIDDLEWARE) {
            return resolve($config['middleware'])->execute(function () {
            });
        } elseif ($config['driver'] === static::DRIVER_JSON) {
            return new JSON($config);
        } elseif ($config['driver'] === static::DRIVER_DYNAMODB) {
            return new DynamoDB($config);
        }

        return static::initPdoDatabase($config, $name);
    }

    /**
     * @return mixed|null
     */
    public function getDefaultRepository()
    {
        return static::$repositories[static::DEFAULT_NAME] ?? null;
    }

    /**
     * @return array
     */
    public static function getRepositories()
    {
        return static::$repositories;
    }
}
