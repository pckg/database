<?php

namespace Pckg\Database\Relation\Helper;

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

    protected function returnRelation($relation, $entity, callable $callback = null)
    {
        $relation = new $relation($this, $entity);

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
    public function hasMany($hasMany, callable $callback = null)
    {
        return $this->returnRelation(HasMany::class, $hasMany, $callback);
    }

    /**
     * @param $hasMany
     *
     * @return HasMany
     */
    public function hasOne($hasOne, $alias = null)
    {
        $relation = new HasOne($this, $hasOne);

        if ($alias) {
            $relation->getRightEntity()->setAlias($alias);
        }

        return $relation;
    }

    /**
     * @param $belongsTo
     *
     * @return BelongsTo
     */
    public function belongsTo($entity, callable $callback = null)
    {
        return $this->returnRelation(BelongsTo::class, $entity, $callback);
    }

    /**
     * @param $hasAndBelongsTo
     *
     * @return HasAndBelongsTo
     */
    public function hasAndBelongsTo($hasAndBelongsTo)
    {
        return new HasAndBelongsTo($this, $hasAndBelongsTo);
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
    public function morphsMany($morphsMany)
    {
        return new MorphsMany($this, $morphsMany);
    }

    /**
     * @param $morphedBy
     *
     * @return MorphedBy
     */
    public function morphedBy($morphedBy)
    {
        // @T00D00
        return new MorphedBy($this, $morphedBy);
    }

}