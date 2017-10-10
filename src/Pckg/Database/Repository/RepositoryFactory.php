<?php namespace Pckg\Database\Repository;

use Exception;
use Pckg\Database\Command\InitDatabase;
use Pckg\Database\Repository;

class RepositoryFactory
{

    protected static $repositories = [];

    const DEFAULT_NAME = 'default';

    public static function getOrCreateRepository($name)
    {
        $name = $name == Repository::class
            ? 'default'
            : str_replace(Repository::class . '.', '', $name);

        $repository = null;
        if (array_key_exists($name, static::$repositories)) {
            $repository = static::$repositories[$name];
        } else {
            if (!context()->exists($name)) {
                /**
                 * Lazy load.
                 */
                $config = config('database.' . $name);
                if (!$config) {
                    throw new Exception("No config found for database connection " . $name);
                }
                $repository = (new InitDatabase())->initPdoDatabase($config, $name);
                context()->bind($name, $repository);
            }

            $repository = context()->get($name);
        }

        if (!$repository) {
            throw new Exception('Cannot prepare repository');
        }

        return $repository;
    }

    public static function createPdoRepository(
        $dsn, $username, $passwd = null, $options = null, $name = self::DEFAULT_NAME
    ) {
        /**
         * Create pure PDO connection.
         */
        $pdoConnection = new \PDO($dsn, $username, $passwd, $options);

        /**
         * Create PDO database repository.
         */
        $connection = new PDO($pdoConnection);

        static::$repositories[$name] = $connection;

        return $connection;
    }

    public function getDefaultRepository()
    {
        return static::$repositories[static::DEFAULT_NAME] ?? null;
    }

}