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
 * @package Pckg\Database\Relation\Helper
 */
trait RelationMethods
{

    /**
     * @param $hasMany
     *
     * @return HasMany
     */
    public function hasMany($hasMany)
    {
        return new HasMany($this, $hasMany);
    }

    /**
     * @param $hasOne
     *
     * @return HasOne
     */
    public function hasOne($hasOne)
    {
        return new HasOne($this, $hasOne);
    }

    /**
     * @param $belongsTo
     *
     * @return BelongsTo
     */
    public function belongsTo($belongsTo)
    {
        return new BelongsTo($this, $belongsTo);
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
        return new MorphedBy($this, $morphedBy);
    }

}