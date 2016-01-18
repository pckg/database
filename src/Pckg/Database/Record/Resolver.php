<?php namespace Pckg\Database\Record;

use Pckg\Concept\Reflect;
use Pckg\Concept\Reflect\Resolver as ResolverInterface;
use Pckg\Database\Record;
use Pckg\Framework\Response;
use Pckg\Framework\Router;

class Resolver implements ResolverInterface
{

    protected $classes = [
        Record::class,
    ];

    protected $router;

    protected $response;

    public function __construct(Router $router, Response $response)
    {
        $this->router = $router;
        $this->response = $response;
    }

    public function resolve($class)
    {
        foreach ($this->classes as $resolvableClass) {
            if (in_array($resolvableClass, class_parents($class))) {
                return Reflect::create($class)
                    ->getEntity()
                    ->inExtendedContext()
                    ->where('slug', $this->router->get('name'))
                    ->oneOrFail(function () {
                        $this->response->notFound('Route not found');
                    });
            }
        }
    }

}