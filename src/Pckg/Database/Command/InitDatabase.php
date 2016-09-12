<?php namespace Pckg\Database\Command;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar;
use Faker\Factory;
use Pckg\Concept\AbstractChainOfReponsibility;
use Pckg\Concept\Context;
use Pckg\Database\Repository;
use Pckg\Database\Repository\Faker as RepositoryFaker;
use Pckg\Database\Repository\PDO as RepositoryPDO;
use Pckg\Framework\Config;
use PDO;

/**
 * Class InitDatabase
 *
 * @package Pckg\Database\Command
 */
class InitDatabase extends AbstractChainOfReponsibility
{

    protected $config;

    protected $context;

    /**
     * @param Config $config
     */
    public function __construct(Config $config, Context $context)
    {
        $this->config = $config;
        $this->context = $context;
    }

    /**
     * @throws \Exception
     */
    public function execute(callable $next)
    {
        /**
         * Skip database initialization if connections are not defined.
         */
        if (!$this->config->get('database')) {
            return $next();
        }

        $configs = $this->config->get('database');
        foreach ($configs as $name => $config) {
            $repository = null;
            if ($config['driver'] == 'faker') {
                $repository = new RepositoryFaker(Factory::create());

            } elseif ($config['driver'] == 'middleware') {
                $repository = resolve($config['middleware'])->execute(
                    function() {
                    }
                );

            } else {
                $repository = $this->initPdoDatabase($config, $name);

            }

            if ($repository) {
                $this->context->bindIfNot(Repository::class, $repository);
                $this->context->bind(Repository::class . '.' . $name, $repository);
            }
        }

        return $next();
    }

    public function initPdoDatabase($config, $name)
    {
        $pdo = new PDO(
            "mysql:host=" . $config['host'] . ";charset=" . $config['charset'] . ";dbname=" . $config['db'],
            $config['user'],
            $config['pass']
        );

        $pdo->uniqueName = $config['host'] . "-" . $config['db'];

        if ($this->context->exists(DebugBar::class)) {
            $debugBar = $this->context->find(DebugBar::class);
            $tracablePdo = new TraceablePDO($pdo);

            if ($debugBar->hasCollector('pdo')) {
                $pdoCollector = $debugBar->getCollector('pdo');

            } else {
                $debugBar->addCollector($pdoCollector = new PDOCollector());

            }

            if (false && !isset($config['default'])) {
                $pdoCollector->addConnection($tracablePdo, 'default');

            } else {
                $pdoCollector->addConnection($tracablePdo, $name);

            }
        }

        return new RepositoryPDO($pdo, $name);
    }

}