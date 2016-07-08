<?php namespace Pckg\Database\Record;

use Exception;
use Pckg\Concept\Reflect;
use Pckg\Concept\Reflect\Resolver as ResolverInterface;
use Pckg\Database\Record;
use Pckg\Framework\Response;
use Pckg\Framework\Router;

class Resolver implements ResolverInterface
{

    protected $router;

    protected $response;

    protected $classes = [];

    public function __construct(Router $router, Response $response)
    {
        $this->router = $router;
        $this->response = $response;
    }

    public function classes()
    {
        /**
         * @T00D00 - this is useless, right?
         */
        return [
            Record::class => function ($class) {
                throw new Exception('Is this needed? Empty class will be created ...');
                return new $class();
                $result = Reflect::create($class)
                    ->getEntity()
                    ->where('slug', $this->router->get('name'))
                    ->oneOrFail(function () use ($class) {
                        $this->response->notFound('Record ' . $class . ' not found, cannot be resolved');
                    });

                return $result;
            },
        ];
    }

    public function resolve($class)
    {
        foreach ($this->classes() as $resolvableClass => $resolvableCallback) {
            if (in_array($resolvableClass, class_parents($class))) {
                return $resolvableCallback($class);
            }
        }
    }

}