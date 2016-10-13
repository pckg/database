<?php namespace Test\Entity;

use Pckg\Database\Entity;
use Test\Record\User;

class Users extends Entity
{

    protected $record = User::class;

    public function userGroup()
    {
        return $this->belongsTo(UserGroups::class)
                    ->foreignKey('user_group_id');
    }

    public function language()
    {
        return $this->belongsTo(Languages::class)
                    ->foreignKey('language_id');
    }

    public function categories()
    {
        return $this->hasAndBelongsTo(Categories::class)
                    ->over('users_categories')
                    ->leftForeignKey('user_id')
                    ->rightForeignKey('category_id');
    }

    public function settings()
    {
        return $this->morphedBy(Settings::class)
                    ->over('settings_morphs');
    }

}