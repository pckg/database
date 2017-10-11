<?php namespace Pckg\Database\Relation\Helper;

use Pckg\Concept\Reflect;
use Pckg\Database\Relation\BelongsTo;
use Pckg\Database\Relation\HasAndBelongsTo;
use Pckg\Database\Relation\HasChildren;
use Pckg\Database\Relation\HasMany;
use Pckg\Database\Relation\HasOne;
use Pckg\Database\Relation\HasParent;
use Pckg\Database\Relation\MorphedBy;
use Pckg\Database\Relation\MorphsMany;

/**
 * Class RelationMethods
 *
 * @package Pckg\Database\Relation\Helper
 */
trait RelationMethods
{

    /**
     * @param $hasMany
     *
     * @return HasMany
     */
    public function hasMany($hasMany, callable $callback = null, $alias = null)
    {
        return $this->returnRelation(HasMany::class, $hasMany, $callback, $alias);
    }

    /**
     * @param               $relation
     * @param               $entity
     * @param callable|null $callback
     * @param null          $alias
     *
     * @return mixed
     */
    protected function returnRelation($relation, $entity, callable $callback = null, $alias = null)
    {
        if (is_string($entity)) {
            $entity = Reflect::create($entity, ['alias' => $alias]);
        }

        $relation = new $relation($this, $entity);

        if ($alias) {
            $relation->getRightEntity()->setAlias($alias);
        }

        if ($callback) {
            $callback($relation);
        }

        return $relation;
    }

    /**
     * @param $hasMany
     *
     * @return HasMany
     */
    public function hasOne($entity, callable $callback = null, $alias = null)
    {
        $relation = $this->returnRelation(HasOne::class, $entity, $callback, $alias);

        return $relation;
    }

    /**
     * @param $belongsTo
     *
     * @return BelongsTo
     */
    public function belongsTo($entity, callable $callback = null, $alias = null)
    {
        return $this->returnRelation(BelongsTo::class, $entity, $callback, $alias);
    }

    /**
     * @param $hasAndBelongsTo
     *
     * @return HasAndBelongsTo
     */
    public function hasAndBelongsTo($entity, callable $callback = null, $alias = null)
    {
        return $this->returnRelation(HasAndBelongsTo::class, $entity, $callback, $alias);
    }

    /**
     * @param $hasParent
     *
     * @return HasParent
     */
    public function hasParent($hasParent)
    {
        return new HasParent($this, $hasParent);
    }

    /**
     * @param $hasChildren
     *
     * @return HasChildren
     */
    public function hasChildren($hasChildren)
    {
        return new HasChildren($this, $hasChildren);
    }

    /**
     * @param $morphsMany
     *
     * @return MorphsMany
     */
    public function morphsMany($entity, callable $callable = null, $alias = null)
    {
        return $this->returnRelation(MorphsMany::class, $entity, $callable, $alias);
    }

    /**
     * @param $morphedBy
     *
     * @return MorphedBy
     */
    public function morphedBy($entity, callable $callable = null, $alias = null)
    {
        return $this->returnRelation(MorphedBy::class, $entity, $callable, $alias);
    }

}