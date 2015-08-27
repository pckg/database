<?php


namespace LFW\Database\Command;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use LFW\Concept\AbstractChainOfReponsibility;
use LFW\Config;
use LFW\Context;
use LFW\Database\Repository\PDO as RepositoryPDO;
use PDO;

/**
 * Class InitDatabase
 * @package LFW\Database\Command
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
    public function execute()
    {
        $config = $this->config->get('database.default');

        $pdo = new \PDO("mysql:host=" . $config['host'] . ";charset=" . $config['charset'] . ";dbname=" . $config['db'], $config['user'], $config['pass']);

        if ($this->context->exists('DebugBar')) {
            $pdoCollector = new TraceablePDO($pdo);
            $this->context->find('DebugBar')->addCollector(new PDOCollector($pdoCollector));
        }

        $repository = new RepositoryPDO($pdo);

        $this->context->bind('Repository', $repository);

        $this->next->execute();
    }

}