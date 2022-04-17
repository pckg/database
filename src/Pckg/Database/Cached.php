<?php

namespace Pckg\Database;

/**
 * Class Cached
 *
 * @package Pckg\Database
 */
class Cached
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var array
     */
    protected $cachedMethods = [
        'all',
        'allAndEach',
        'allAndIf',
        'allAnd',
        'one',
        'oneAndIf',
        'oneAnd',
    ];

    /**
     * @var int
     */
    protected $time;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var null|string
     */
    protected $key;

    /**
     * Cached constructor.
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity, $time, $type, $key = null)
    {
        $this->entity = $entity;
        $this->type = $type;
        $this->time = $time;
        $this->key = $key;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        /**
         * Directly call non-cached methods.
         */
        if (!in_array($method, $this->cachedMethods)) {
            return $this->entity->{$method}(...$args);
        }

        /**
         * Cache cached methods.
         */
        return $this->cached($method, $args);
    }

    /**
     * @throws \Exception
     */
    public function cached($method, $args)
    {
        /**
         * Build and receive cache key.
         */
        $key = $this->key ?? $this->entity->getQuery()->getCacheKey();

        /**
         * Get cache or make real request and cache result.
         */
        return measure(
            'Getting cached key ' . $key,
            function () use ($key, $method, $args) {
                return cache()->cache(
                    $key,
                    function () use ($key, $method, $args) {
                        return measure(
                            'Caching key ' . $key,
                            function () use ($method, $args) {
                                return $this->entity->{$method}(...$args);
                            }
                        );
                    },
                    $this->type,
                    $this->time
                );
            }
        );
    }
}
