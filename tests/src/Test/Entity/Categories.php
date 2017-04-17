<?php namespace Test\Entity;

use Pckg\Database\Entity;

class Categories extends Entity
{

    public function users()
    {
        return $this->hasAndBelongsTo(Users::class)
                    ->over('users_categories')
                    ->leftForeignKey('category_id')
                    ->rightForeignKey('user_id');
    }

    public function settings()
    {
        return $this->morphedBy(Settings::class)
                    ->over('settings_morphs')
                    ->rightForeignKey('setting_id');
    }

}