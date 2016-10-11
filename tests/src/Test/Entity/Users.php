<?php namespace Test\Entity;

use Pckg\Database\Entity;

class Users extends Entity
{

    public function userGroup()
    {
        return $this->belongsTo(UserGroups::class)
                    ->foreignKey('user_id');
    }

    public function categories()
    {
        return $this->hasAndBelongsTo(Categories::class)
                    ->over('user_categories')
                    ->leftForeignKey('user_id')
                    ->rightForeignKey('category_id');
    }

    public function language()
    {
        return $this->belongsTo(Languages::class)
                    ->foreignKey('language_id');
    }

}