<?php


namespace Pckg\Database\Command;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use Faker\Factory;
use Pckg\Concept\AbstractChainOfReponsibility;
use Pckg\Framework\Config;
use Pckg\Concept\Context;
use Pckg\Database\Repository\PDO as RepositoryPDO;
use Pckg\Database\Repository\Faker as RepositoryFaker;
use PDO;

/**
 * Class InitDatabase
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
        foreach ($this->config->get('database') as $name => $config) {
            if ($config['driver'] == 'faker') {
                $repository = new RepositoryFaker(Factory::create());

            } else {
                $pdo = new \PDO("mysql:host=" . $config['host'] . ";charset=" . $config['charset'] . ";dbname=" . $config['db'], $config['user'], $config['pass']);

                if ($this->context->exists('DebugBar')) {
                    $debugBar = $this->context->find('DebugBar');
                    $tracablePdo = new TraceablePDO($pdo);

                    if ($debugBar->hasCollector('pdo')) {
                        $pdoCollector = $debugBar->getCollector('pdo');
                        $pdoCollector->addConnection($tracablePdo, $name);
                    } else {
                        $debugBar->addCollector(new PDOCollector($tracablePdo));
                    }
                }

                $repository = new RepositoryPDO($pdo);
            }

            $this->context->bindIfNot('Repository', $repository);
            $this->context->bind('Repository.' . $name, $repository);
        }

        return $next();
    }

}