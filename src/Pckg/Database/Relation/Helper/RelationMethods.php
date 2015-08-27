<?php

namespace Pckg\Database\Relation\Helper;

use Pckg\Database\Relation\BelongsTo;
use Pckg\Database\Relation\HasAndBelongsTo;
use Pckg\Database\Relation\HasChildren;
use Pckg\Database\Relation\HasMany;
use Pckg\Database\Relation\HasOne;
use Pckg\Database\Relation\HasParent;

/**
 * Class RelationMethods
 * @package Pckg\Database\Relation\Helper
 */
trait RelationMethods
{

    /**
     * @param $hasMany
     * @return mixed
     */
    public function hasMany($hasMany)
    {
        return new HasMany($this, $hasMany);
    }

    /**
     * @param $hasOne
     * @return mixed
     */
    public function hasOne($hasOne)
    {
        return new HasOne($this, $hasOne);
    }

    /**
     * @param $belongsTo
     * @return mixed
     */
    public function belongsTo($belongsTo)
    {
        return new BelongsTo($this, $belongsTo);
    }

    /**
     * @param $hasAndBelongsTo
     * @return mixed
     */
    public function hasAndBelongsTo($hasAndBelongsTo)
    {
        return new HasAndBelongsTo($this, $hasAndBelongsTo);
    }

    /**
     * @param $hasParent
     * @return mixed
     */
    public function hasParent($hasParent)
    {
        return new HasParent($this, $hasParent);
    }

    /**
     * @param $hasChildren
     * @return mixed
     */
    public function hasChildren($hasChildren)
    {
        return new HasChildren($this, $hasChildren);
    }

}