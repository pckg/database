<?php namespace Pckg\Database\Command;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar;
use Exception;
use Faker\Factory;
use Pckg\Concept\AbstractChainOfReponsibility;
use Pckg\Database\Repository;
use Pckg\Database\Repository\Faker as RepositoryFaker;
use Pckg\Database\Repository\PDO as RepositoryPDO;
use PDO;
use PDOException;

/**
 * Class InitDatabase
 *
 * @package Pckg\Database\Command
 */
class InitDatabase extends AbstractChainOfReponsibility
{

    /**
     * @throws Exception
     */
    public function execute(callable $next)
    {
        foreach (config('database', []) as $name => $config) {
            /**
             * Skip lazy initialize connections which will be estamblished on demand.
             */
            if (isset($config['lazy'])) {
                continue;
            }

            /**
             * Create faker, middleware or pdo repository.
             */
            $repository = $this->getRepositoryByConfig($config, $name);

            /**
             * Bind repository to context so we can reuse it later.
             */
            context()->bindIfNot(Repository::class, $repository);
            context()->bind(Repository::class . '.' . $name, $repository);
        }

        return $next();
    }

    /**
     * Instantiate connection for defined driver.
     *
     * @param $config
     * @param $name
     *
     * @return RepositoryFaker|RepositoryPDO
     * @throws Exception
     */
    protected function getRepositoryByConfig($config, $name)
    {
        if ($config['driver'] == 'faker') {
            return new RepositoryFaker(Factory::create());

        } elseif ($config['driver'] == 'middleware') {
            return resolve($config['middleware'])->execute(
                function() {
                }
            );

        }

        return $this->initPdoDatabase($config, $name);
    }

    public function createPdoConnectionByConfig($config)
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

    public function initPdoDatabase($config, $name)
    {
        try {
            $pdo = $this->createPdoConnectionByConfig($config);
        } catch (PDOException $e) {
            throw new Exception('Cannon instantiate database connection: ' . $e->getMessage());

        }

        $pdo->uniqueName = $config['host'] . "-" . $config['db'];

        $this->checkDebugBar($pdo, $name);

        return new RepositoryPDO($pdo, $name);
    }

    protected function checkDebugBar($pdo, $name)
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