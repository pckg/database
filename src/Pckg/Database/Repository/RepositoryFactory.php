<?php namespace Pckg\Database\Repository;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar;
use Exception;
use Faker\Factory;
use Pckg\Database\Repository;
use Pckg\Database\Repository\PDO as RepositoryPDO;
use PDO;
use PDOException;

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
                $repository = RepositoryFactory::initPdoDatabase($config, $name);
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

    public static function createRepositoryConnection($config, $name)
    {
        /**
         * Create faker, middleware or pdo repository.
         */
        $repository = static::getRepositoryByConfig($config, $name);

        /**
         * Bind repository to context so we can reuse it later.
         */
        context()->bindIfNot(Repository::class, $repository);
        context()->bind(Repository::class . '.' . $name, $repository);

        return $repository;
    }

    /**
     * Instantiate connection for defined driver.
     *
     * @param $config
     * @param $name
     *
     * @return Faker|RepositoryPDO
     * @throws Exception
     */
    protected static function getRepositoryByConfig($config, $name)
    {
        if ($config['driver'] == 'faker') {
            return new Faker(Factory::create());
        } elseif ($config['driver'] == 'middleware') {
            return resolve($config['middleware'])->execute(
                function() {
                }
            );
        }

        return static::initPdoDatabase($config, $name);
    }

    public static function createPdoConnectionByConfig($config)
    {
        return new PDO(
            "mysql:host=" . $config['host'] . ";charset=" . ($config['charset'] ?? 'utf8') .
            (isset($config['db'])
                ? ";dbname=" . $config['db']
                : ''),
            $config['user'],
            $config['pass']
        );
    }

    public static function initPdoDatabase($config, $name)
    {
        try {
            $pdo = static::createPdoConnectionByConfig($config);
        } catch (PDOException $e) {
            throw new Exception('Cannon instantiate database connection: ' . $e->getMessage());
        }

        $pdo->uniqueName = $config['host'] . "-" . $config['db'];

        static::checkDebugBar($pdo, $name);

        return new RepositoryPDO($pdo, $name);
    }

    protected static function checkDebugBar($pdo, $name)
    {
        if (context()->exists(DebugBar::class)) {
            $debugBar = context()->find(DebugBar::class);
            $tracablePdo = new TraceablePDO($pdo);

            if ($debugBar->hasCollector('pdo')) {
                $pdoCollector = $debugBar->getCollector('pdo');
            } else {
                $debugBar->addCollector($pdoCollector = new PDOCollector());
            }

            $pdoCollector->addConnection($tracablePdo, $name);
        }
    }

}